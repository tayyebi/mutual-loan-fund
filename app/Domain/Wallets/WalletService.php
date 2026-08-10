<?php

namespace App\Domain\Wallets;

use App\Domain\Audit\AuditAction;
use App\Domain\Audit\AuditRecorder;
use App\Models\GroupMembership;
use App\Models\User;
use App\Models\Wallet;
use InvalidArgumentException;

/**
 * External wallet addresses belonging to members.
 *
 * The application is non-custodial. Nothing here accepts a private key, a seed
 * phrase or a wallet password, and no such field exists to store one in.
 */
class WalletService
{
    public function __construct(private readonly AuditRecorder $audit) {}

    /**
     * @param  array{currency: string, network: string, address: string, label?: ?string}  $attributes
     */
    public function register(GroupMembership $membership, array $attributes, User $actor): Wallet
    {
        $network = $attributes['network'];
        $address = trim($attributes['address']);

        $this->assertValidAddress($network, $address);

        $wallet = Wallet::create([
            'group_id' => $membership->group_id,
            'member_id' => $membership->getKey(),
            'currency' => strtoupper($attributes['currency']),
            'network' => $network,
            'address' => $address,
            'label' => $attributes['label'] ?? null,
            'status' => Wallet::STATUS_ACTIVE,
        ]);

        $this->audit->record(
            AuditAction::WALLET_ADDED,
            group: $membership->group,
            actor: $actor,
            object: $wallet,
            new: ['network' => $wallet->network, 'currency' => $wallet->currency, 'address' => $wallet->address]
        );

        return $wallet;
    }

    /**
     * @param  array{label?: ?string, status?: string}  $attributes
     */
    public function update(Wallet $wallet, array $attributes, User $actor): Wallet
    {
        $before = $wallet->only(['label', 'status']);

        // The address itself is immutable: a wallet that has appeared in
        // transaction evidence must keep meaning the same address. Register a
        // new wallet instead.
        $wallet->fill(array_intersect_key($attributes, array_flip(['label', 'status'])))->save();

        $this->audit->record(
            AuditAction::WALLET_CHANGED,
            group: $wallet->group,
            actor: $actor,
            object: $wallet,
            old: $before,
            new: $wallet->only(['label', 'status'])
        );

        return $wallet;
    }

    private function assertValidAddress(string $network, string $address): void
    {
        $pattern = config("fund.networks.{$network}.address_pattern");

        if (! $pattern) {
            throw new InvalidArgumentException(__('exceptions.network_unsupported', ['network' => $network]));
        }

        if (! preg_match($pattern, $address)) {
            throw new InvalidArgumentException(__('exceptions.address_invalid', ['network' => $network]));
        }
    }
}
