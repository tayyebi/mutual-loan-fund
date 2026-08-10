<?php

namespace App\Domain\Accounting;

use App\Domain\Accounting\Exceptions\PostingException;
use App\Models\Account;
use App\Models\Group;
use App\Models\Treasury;

/**
 * Creates and resolves a group's chart of accounts.
 *
 * Cost-center requirement is a property of the account, which is what §23 of the
 * specification asks for: Loans Receivable and Interest Income must always be
 * attributed to a member, treasury accounts never are.
 */
class ChartOfAccounts
{
    /**
     * Accounts every group starts with. Treasury accounts are added under 1100
     * as treasuries are created.
     *
     * @return list<array{code: string, name: string, type: string, parent: ?string, cost_center: bool}>
     */
    public static function defaults(): array
    {
        return [
            ['code' => '1000', 'name' => 'Assets', 'type' => Account::TYPE_ASSET, 'parent' => null, 'cost_center' => false],
            ['code' => '1100', 'name' => 'Treasuries', 'type' => Account::TYPE_ASSET, 'parent' => '1000', 'cost_center' => false],
            ['code' => Account::LOANS_RECEIVABLE, 'name' => 'Loans Receivable', 'type' => Account::TYPE_ASSET, 'parent' => '1000', 'cost_center' => true],

            ['code' => '2000', 'name' => 'Liabilities', 'type' => Account::TYPE_LIABILITY, 'parent' => null, 'cost_center' => false],
            ['code' => Account::MEMBER_PAYABLES, 'name' => 'Member Payables', 'type' => Account::TYPE_LIABILITY, 'parent' => '2000', 'cost_center' => true],

            ['code' => '3000', 'name' => 'Equity', 'type' => Account::TYPE_EQUITY, 'parent' => null, 'cost_center' => false],
            ['code' => Account::FUND_CAPITAL, 'name' => 'Fund Capital', 'type' => Account::TYPE_EQUITY, 'parent' => '3000', 'cost_center' => true],
            ['code' => Account::RETAINED_RESULTS, 'name' => 'Retained Results', 'type' => Account::TYPE_EQUITY, 'parent' => '3000', 'cost_center' => false],

            ['code' => '4000', 'name' => 'Income', 'type' => Account::TYPE_INCOME, 'parent' => null, 'cost_center' => false],
            ['code' => Account::LOAN_INTEREST_INCOME, 'name' => 'Loan Interest Income', 'type' => Account::TYPE_INCOME, 'parent' => '4000', 'cost_center' => true],
            ['code' => Account::OTHER_INCOME, 'name' => 'Other Income', 'type' => Account::TYPE_INCOME, 'parent' => '4000', 'cost_center' => false],
            ['code' => Account::EXCHANGE_GAIN, 'name' => 'Exchange Gain', 'type' => Account::TYPE_INCOME, 'parent' => '4000', 'cost_center' => false],

            ['code' => '5000', 'name' => 'Expenses', 'type' => Account::TYPE_EXPENSE, 'parent' => null, 'cost_center' => false],
            ['code' => Account::NETWORK_FEES, 'name' => 'Network Fees', 'type' => Account::TYPE_EXPENSE, 'parent' => '5000', 'cost_center' => false],
            ['code' => Account::BANK_FEES, 'name' => 'Bank Fees', 'type' => Account::TYPE_EXPENSE, 'parent' => '5000', 'cost_center' => false],
            ['code' => Account::OTHER_EXPENSES, 'name' => 'Other Expenses', 'type' => Account::TYPE_EXPENSE, 'parent' => '5000', 'cost_center' => false],
            ['code' => Account::EXCHANGE_LOSS, 'name' => 'Exchange Loss', 'type' => Account::TYPE_EXPENSE, 'parent' => '5000', 'cost_center' => false],
        ];
    }

    /**
     * Install the default chart for a newly created group.
     */
    public function install(Group $group): void
    {
        $created = [];

        foreach (self::defaults() as $definition) {
            $created[$definition['code']] = Account::create([
                'group_id' => $group->getKey(),
                'code' => $definition['code'],
                'name' => $definition['name'],
                'type' => $definition['type'],
                'parent_id' => $definition['parent'] ? $created[$definition['parent']]->getKey() : null,
                'requires_cost_center' => $definition['cost_center'],
                'is_system' => true,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Every treasury gets its own asset account under 1100, so a treasury
     * balance is a ledger balance rather than a mutable column.
     */
    public function createTreasuryAccount(Treasury $treasury): Account
    {
        $group = $treasury->group;
        $parent = $this->find($group, '1100');

        return Account::create([
            'group_id' => $group->getKey(),
            'code' => $this->nextTreasuryCode($group),
            'name' => $treasury->name,
            'type' => Account::TYPE_ASSET,
            'currency' => $treasury->currency,
            'parent_id' => $parent?->getKey(),
            'requires_cost_center' => false,
            'is_system' => true,
            'is_active' => true,
        ]);
    }

    public function find(Group $group, string $code): ?Account
    {
        return Account::query()->forGroup($group)->where('code', $code)->first();
    }

    /**
     * @throws PostingException when a system account is missing
     */
    public function require(Group $group, string $code): Account
    {
        return $this->find($group, $code) ?? throw new PostingException(
            __('exceptions.chart_account_missing', ['group' => $group->name, 'code' => $code])
        );
    }

    public function feeAccountFor(Treasury $treasury): Account
    {
        return $this->require(
            $treasury->group,
            $treasury->isCrypto() ? Account::NETWORK_FEES : Account::BANK_FEES
        );
    }

    /**
     * Treasury accounts occupy 1101–1199, allocated in creation order.
     */
    private function nextTreasuryCode(Group $group): string
    {
        $last = Account::query()
            ->forGroup($group)
            ->whereBetween('code', ['1101', '1199'])
            ->orderByDesc('code')
            ->value('code');

        $next = $last ? ((int) $last) + 1 : 1101;

        if ($next > 1199) {
            throw new PostingException(__('exceptions.no_treasury_codes_remaining'));
        }

        return (string) $next;
    }
}
