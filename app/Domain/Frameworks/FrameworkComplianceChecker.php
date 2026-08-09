<?php

namespace App\Domain\Frameworks;

use App\Domain\Money\Decimal;
use App\Domain\Policies\PolicyConfig;
use App\Domain\Policies\PolicyField;
use App\Models\FinancialFramework;

/**
 * Compares a policy document against a chosen financial framework's rules.
 *
 * This is deliberately the same shape as PolicyValidator::validate(): an
 * array<string, string> keyed "category.field", so the same Blade rendering
 * pattern (foreach over the array, alert-warn) works for both. Unlike
 * PolicyValidator, nothing here is ever blocking — it is read, never
 * enforced, by PolicyService or PolicyPublisher.
 */
class FrameworkComplianceChecker
{
    public const OPERATOR_EQUALS = 'equals';

    public const OPERATOR_MAX = 'max';

    public const OPERATOR_MIN = 'min';

    /**
     * @return array<string, string> keyed by "category.field"
     */
    public function check(PolicyConfig $config, ?FinancialFramework $framework): array
    {
        if ($framework === null) {
            return [];
        }

        $warnings = [];

        foreach ($framework->rules() as $rule) {
            $key = "{$rule['category']}.{$rule['field']}";
            $category = $config->category($rule['category']);
            $field = $category::field($rule['field']);
            $current = $category->get($rule['field']);

            if (! $this->satisfies($field, $current, $rule['operator'], $rule['value'])) {
                $warnings[$key] = __('exceptions.framework_drift', [
                    'field' => $field->label,
                    'current' => $field->display($current),
                    'framework' => $framework->name,
                    'requirement' => $rule['label'],
                ]);
            }
        }

        return $warnings;
    }

    private function satisfies(PolicyField $field, mixed $current, string $operator, mixed $target): bool
    {
        if ($field->type === PolicyField::TYPE_BOOL) {
            return $operator !== self::OPERATOR_EQUALS
                || (bool) $current === filter_var($target, FILTER_VALIDATE_BOOLEAN);
        }

        if ($field->isDecimal()) {
            $currentValue = Decimal::of((string) ($current ?? '0'));
            $targetValue = Decimal::of((string) $target);

            return match ($operator) {
                self::OPERATOR_EQUALS => $currentValue->equals($targetValue),
                self::OPERATOR_MAX => $currentValue->lessThanOrEqual($targetValue),
                self::OPERATOR_MIN => $currentValue->greaterThanOrEqual($targetValue),
                default => true,
            };
        }

        // Enum/string/integer fields: equality only, sufficient for the seeded
        // rule set (loans.interest_method 'equals' 'none').
        return $operator !== self::OPERATOR_EQUALS || (string) $current === (string) $target;
    }
}
