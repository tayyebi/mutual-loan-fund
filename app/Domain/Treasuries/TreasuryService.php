<?php

namespace App\Domain\Treasuries;

use App\Domain\Accounting\ChartOfAccounts;
use App\Domain\Audit\AuditAction;
use App\Domain\Audit\AuditRecorder;
use App\Models\Group;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Treasuries record where the fund's assets are held.
 *
 * Creating one also creates its ledger account, because a treasury with no
 * account could hold money the ledger cannot see.
 */
class TreasuryService
{
    public function __construct(
        private readonly ChartOfAccounts $chart,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * @param  array{name: string, type: string, currency: string, network?: ?string, external_identifier?: ?string}  $attributes
     */
    public function create(Group $group, array $attributes, User $actor): Treasury
    {
        $this->assertNetworkMatchesType($attributes);

        return DB::transaction(function () use ($group, $attributes, $actor) {
            $treasury = Treasury::create([
                'group_id' => $group->getKey(),
                'name' => $attributes['name'],
                'type' => $attributes['type'],
                'currency' => strtoupper($attributes['currency']),
                'network' => $attributes['network'] ?? null,
                'external_identifier' => $attributes['external_identifier'] ?? null,
                'status' => Treasury::STATUS_ACTIVE,
            ]);

            $account = $this->chart->createTreasuryAccount($treasury);
            $treasury->forceFill(['account_id' => $account->getKey()])->save();

            $this->audit->record(
                AuditAction::TREASURY_CREATED,
                group: $group,
                actor: $actor,
                object: $treasury,
                new: [
                    'name' => $treasury->name,
                    'type' => $treasury->type,
                    'currency' => $treasury->currency,
                    'network' => $treasury->network,
                    'account' => $account->code,
                ]
            );

            return $treasury->refresh();
        });
    }

    /**
     * Only presentation and status are editable.
     *
     * A treasury's currency, type and network are frozen once it exists: changing
     * them would silently re-denominate every posting already made against it.
     *
     * @param  array{name?: string, external_identifier?: ?string, status?: string}  $attributes
     */
    public function update(Treasury $treasury, array $attributes, User $actor): Treasury
    {
        $before = $treasury->only(['name', 'external_identifier', 'status']);

        $treasury->fill(array_intersect_key($attributes, array_flip([
            'name', 'external_identifier', 'status',
        ])))->save();

        if ($treasury->wasChanged('name') && $treasury->account) {
            $treasury->account->forceFill(['name' => $treasury->name])->save();
        }

        $this->audit->record(
            AuditAction::TREASURY_UPDATED,
            group: $treasury->group,
            actor: $actor,
            object: $treasury,
            old: $before,
            new: $treasury->only(['name', 'external_identifier', 'status'])
        );

        return $treasury;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assertNetworkMatchesType(array $attributes): void
    {
        $isCrypto = ($attributes['type'] ?? null) === Treasury::TYPE_CRYPTO;
        $network = $attributes['network'] ?? null;

        if ($isCrypto && blank($network)) {
            throw new InvalidArgumentException('A crypto treasury needs a network.');
        }

        if (! $isCrypto && filled($network)) {
            throw new InvalidArgumentException('Only crypto treasuries have a network.');
        }

        if ($isCrypto && ! array_key_exists($network, (array) config('fund.networks'))) {
            throw new InvalidArgumentException("Unsupported network '{$network}'.");
        }
    }
}
