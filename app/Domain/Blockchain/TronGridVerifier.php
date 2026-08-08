<?php

namespace App\Domain\Blockchain;

use App\Domain\Money\Decimal;
use App\Models\Transaction;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Verifies TRC-20 transfers through TronGrid.
 *
 * The check reads the treasury address's own TRC-20 transfer list and looks for
 * the claimed hash, so addresses and amounts are compared in the same base58 and
 * decimal forms the API returns — no hand-rolled address decoding stands between
 * the chain and the fund's money.
 *
 * Finality is established by asking the solidity node, which only serves
 * irreversible blocks.
 */
class TronGridVerifier implements ChainVerifier
{
    public function verify(Transaction $transaction): ChainVerification
    {
        $treasury = $transaction->treasury;

        if (! $treasury || blank($treasury->external_identifier)) {
            return ChainVerification::rejected('The treasury has no address to compare against.');
        }

        if ($transaction->network !== 'TRON') {
            return ChainVerification::unchecked("No automated verifier for network {$transaction->network}.");
        }

        $hash = strtolower((string) $transaction->tx_hash);

        try {
            $transfers = $this->client()
                ->get("/v1/accounts/{$treasury->external_identifier}/transactions/trc20", [
                    'limit' => 200,
                    'contract_address' => config('fund.blockchain.trongrid.usdt_contract'),
                ])
                ->throw()
                ->json('data', []);
        } catch (Throwable $e) {
            // A verifier that cannot reach the chain says so; it never guesses.
            return ChainVerification::unchecked('Could not reach TronGrid: '.$e->getMessage());
        }

        $match = collect($transfers)->first(
            fn (array $transfer) => strtolower((string) ($transfer['transaction_id'] ?? '')) === $hash
        );

        if (! $match) {
            return ChainVerification::rejected(
                'No transfer with that hash was found among recent transfers to this treasury.'
            );
        }

        if (($match['to'] ?? null) !== $treasury->external_identifier) {
            return ChainVerification::rejected('The transfer was not sent to this treasury address.', $match);
        }

        $decimals = (int) ($match['token_info']['decimals'] ?? 6);
        $received = Decimal::of((string) ($match['value'] ?? '0'))
            ->dividedBy(bcpow('10', (string) $decimals), Decimal::MONEY_SCALE);

        $expected = Decimal::of((string) $transaction->amount);

        if (! $received->equals($expected)) {
            return ChainVerification::rejected(
                "The chain shows {$received->format(6)} but the submission claims {$expected->format(6)}.",
                $match
            );
        }

        if (! $this->isIrreversible($hash)) {
            return ChainVerification::rejected('The transfer has not yet reached an irreversible block.', $match);
        }

        return ChainVerification::confirmed(
            (string) ($match['from'] ?? ''),
            (string) $match['to'],
            $received,
            confirmations: null,
            evidence: $match
        );
    }

    /**
     * The solidity node only serves blocks that can no longer be reverted, so a
     * transaction it knows about is final.
     */
    private function isIrreversible(string $hash): bool
    {
        try {
            $response = $this->client()
                ->post('/walletsolidity/gettransactionbyid', ['value' => $hash])
                ->throw()
                ->json();

            return filled($response['txID'] ?? null);
        } catch (Throwable) {
            return false;
        }
    }

    private function client(): PendingRequest
    {
        $request = Http::baseUrl((string) config('fund.blockchain.trongrid.endpoint'))
            ->timeout(15)
            ->acceptJson();

        $key = config('fund.blockchain.trongrid.api_key');

        return $key ? $request->withHeaders(['TRON-PRO-API-KEY' => $key]) : $request;
    }
}
