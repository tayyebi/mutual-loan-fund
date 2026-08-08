<?php

namespace App\Domain\Policies;

use App\Domain\Policies\Categories\AccountingPolicy;
use App\Domain\Policies\Categories\ContributionPolicy;
use App\Domain\Policies\Categories\LoanPolicy;
use App\Domain\Policies\Categories\MembershipPolicy;
use App\Domain\Policies\Categories\RepaymentPolicy;
use App\Domain\Policies\Categories\TreasuryPolicy;
use OutOfBoundsException;

/**
 * The whole policy document for one version.
 *
 * Group financial rules live in this structured JSON document rather than in
 * mutable columns on `groups`, so a rule change is a new immutable version
 * instead of an in-place edit that would rewrite history.
 *
 * Additional categories can be added to CATEGORIES later; nothing else in the
 * application needs to change.
 */
final class PolicyConfig
{
    /** @var list<class-string<PolicyCategory>> */
    public const CATEGORIES = [
        LoanPolicy::class,
        ContributionPolicy::class,
        RepaymentPolicy::class,
        MembershipPolicy::class,
        AccountingPolicy::class,
        TreasuryPolicy::class,
    ];

    /**
     * @param  array<string, PolicyCategory>  $categories
     */
    private function __construct(private readonly array $categories) {}

    public static function defaults(): self
    {
        return self::fromArray([]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $categories = [];

        foreach (self::CATEGORIES as $class) {
            $key = $class::key();
            $categories[$key] = $class::fromArray((array) ($data[$key] ?? []));
        }

        return new self($categories);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function toArray(): array
    {
        $data = [];

        foreach ($this->categories as $key => $category) {
            $data[$key] = $category->toArray();
        }

        return $data;
    }

    /**
     * @return array<string, PolicyCategory>
     */
    public function categories(): array
    {
        return $this->categories;
    }

    public function category(string $key): PolicyCategory
    {
        if (! isset($this->categories[$key])) {
            throw new OutOfBoundsException("Unknown policy category '{$key}'.");
        }

        return $this->categories[$key];
    }

    public function loans(): LoanPolicy
    {
        /** @var LoanPolicy */
        return $this->category(LoanPolicy::key());
    }

    public function contributions(): ContributionPolicy
    {
        /** @var ContributionPolicy */
        return $this->category(ContributionPolicy::key());
    }

    public function repayments(): RepaymentPolicy
    {
        /** @var RepaymentPolicy */
        return $this->category(RepaymentPolicy::key());
    }

    public function membership(): MembershipPolicy
    {
        /** @var MembershipPolicy */
        return $this->category(MembershipPolicy::key());
    }

    public function accounting(): AccountingPolicy
    {
        /** @var AccountingPolicy */
        return $this->category(AccountingPolicy::key());
    }

    public function treasury(): TreasuryPolicy
    {
        /** @var TreasuryPolicy */
        return $this->category(TreasuryPolicy::key());
    }

    /**
     * Supports the documented reading style: $policy->loans->maximum_amount
     */
    public function __get(string $name): PolicyCategory
    {
        return $this->category($name);
    }

    /**
     * Merge submitted form values over the current document. Values for unknown
     * keys are ignored, so a hand-crafted request cannot inject configuration.
     *
     * @param  array<string, array<string, mixed>>  $input
     */
    public function merged(array $input): self
    {
        $data = $this->toArray();

        foreach (self::CATEGORIES as $class) {
            $key = $class::key();

            if (! isset($input[$key]) || ! is_array($input[$key])) {
                continue;
            }

            foreach ($class::fields() as $field) {
                if (array_key_exists($field->key, $input[$key])) {
                    $data[$key][$field->key] = $input[$key][$field->key];
                } elseif ($field->type === PolicyField::TYPE_BOOL) {
                    // An unchecked checkbox is simply absent from the request.
                    $data[$key][$field->key] = false;
                }
            }
        }

        return self::fromArray($data);
    }
}
