<?php

namespace Database\Seeders;

use App\Models\FinancialFramework;
use Illuminate\Database\Seeder;

/**
 * The catalogue of financial frameworks a group administrator may optionally
 * point their group at. Reference data, not created through any UI, so it is
 * inserted directly rather than through a domain service — the same
 * exception DemoFundSeeder makes for User::firstOrCreate.
 */
class FinancialFrameworkSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->frameworks() as $framework) {
            FinancialFramework::updateOrCreate(
                ['key' => $framework['key']],
                $framework
            );
        }
    }

    /**
     * @return list<array{key: string, name: string, description: string, rules: array}>
     */
    private function frameworks(): array
    {
        $noInterest = fn (string $label) => [
            [
                'category' => 'loans',
                'field' => 'interest_rate',
                'operator' => 'equals',
                'value' => '0',
                'label' => "requires 0% interest — {$label}",
            ],
            [
                'category' => 'loans',
                'field' => 'interest_method',
                'operator' => 'equals',
                'value' => 'none',
                'label' => "requires no interest method — {$label}",
            ],
        ];

        return [
            [
                'key' => 'islamic',
                'name' => 'Islamic Finance',
                'description' => 'Prohibits interest (riba) on loans between members, in line with Islamic finance principles.',
                'rules' => $noInterest('riba (interest) is prohibited'),
            ],
            [
                'key' => 'conventional',
                'name' => 'Conventional Finance',
                'description' => "No additional constraints beyond the platform's own bounds. A label only — selecting it declares no special rule set.",
                'rules' => [],
            ],
            [
                'key' => 'cooperative',
                'name' => 'Cooperative Finance',
                'description' => 'Rochdale-style mutual aid: not-for-profit lending between members, with no interest charged.',
                'rules' => $noInterest('cooperative, not-for-profit lending charges no interest'),
            ],
            [
                'key' => 'microfinance',
                'name' => 'Microfinance',
                'description' => 'Grameen-style small, short-term loans: interest is capped, and early repayment is always allowed.',
                'rules' => [
                    [
                        'category' => 'loans',
                        'field' => 'interest_rate',
                        'operator' => 'max',
                        'value' => '24',
                        'label' => 'must not exceed 24% — microfinance caps interest',
                    ],
                    [
                        'category' => 'loans',
                        'field' => 'early_repayment_allowed',
                        'operator' => 'equals',
                        'value' => 'true',
                        'label' => 'requires early repayment to always be allowed',
                    ],
                ],
            ],
        ];
    }
}
