<?php

namespace App\Domain\Treasuries;

use App\Domain\Accounting\LedgerBalances;
use App\Domain\Audit\AuditAction;
use App\Domain\Audit\AuditRecorder;
use App\Domain\Money\Decimal;
use App\Models\Treasury;
use App\Models\TreasuryReconciliation;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Compares what the outside world says a treasury holds with what the ledger
 * says it holds.
 *
 * A difference is recorded and shown, never silently absorbed: adjusting the
 * ledger to match an external balance would destroy the very discrepancy the
 * fund needs to investigate. Correcting a genuine error is a separate, reasoned
 * accounting adjustment.
 */
class ReconciliationService
{
    public function __construct(
        private readonly LedgerBalances $balances,
        private readonly AuditRecorder $audit,
    ) {}

    public function reconcile(
        Treasury $treasury,
        Decimal $externalBalance,
        Carbon $asOf,
        User $actor,
        ?string $note = null,
    ): TreasuryReconciliation {
        $ledger = $this->balances->treasuryBalance($treasury, $asOf)->amount;
        $difference = $externalBalance->minus($ledger);

        $reconciliation = TreasuryReconciliation::create([
            'group_id' => $treasury->group_id,
            'treasury_id' => $treasury->getKey(),
            'as_of' => $asOf,
            'external_balance' => $externalBalance->toString(),
            'ledger_balance' => $ledger->toString(),
            'difference' => $difference->toString(),
            'currency' => $treasury->currency,
            'note' => $note,
            'performed_by' => $actor->getKey(),
        ]);

        $this->audit->record(
            AuditAction::TREASURY_RECONCILED,
            group: $treasury->group,
            actor: $actor,
            object: $reconciliation,
            new: [
                'treasury' => $treasury->name,
                'external' => $externalBalance->toString(),
                'ledger' => $ledger->toString(),
                'difference' => $difference->toString(),
            ]
        );

        return $reconciliation;
    }

    public function latest(Treasury $treasury): ?TreasuryReconciliation
    {
        return $treasury->reconciliations()->orderByDesc('as_of')->orderByDesc('id')->first();
    }
}
