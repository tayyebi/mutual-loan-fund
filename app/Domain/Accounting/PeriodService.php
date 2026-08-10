<?php

namespace App\Domain\Accounting;

use App\Domain\Accounting\Exceptions\ClosedPeriodException;
use App\Domain\Audit\AuditAction;
use App\Domain\Audit\AuditRecorder;
use App\Models\AccountingPeriod;
use App\Models\Group;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Monthly accounting periods. Postings are refused into a closed period; a
 * correction for a closed month is posted as an adjustment in an open one.
 */
class PeriodService
{
    public function __construct(private readonly AuditRecorder $audit) {}

    /**
     * The period a posting date falls in, created on demand.
     */
    public function periodFor(Group $group, Carbon $date): AccountingPeriod
    {
        return AccountingPeriod::firstOrCreate(
            [
                'group_id' => $group->getKey(),
                'year' => (int) $date->year,
                'month' => (int) $date->month,
            ],
            ['status' => AccountingPeriod::STATUS_OPEN]
        );
    }

    /**
     * @throws ClosedPeriodException
     */
    public function requireOpenPeriod(Group $group, Carbon $date): AccountingPeriod
    {
        $period = $this->periodFor($group, $date);

        if ($period->isClosed()) {
            throw new ClosedPeriodException($period);
        }

        return $period;
    }

    /**
     * @return Collection<int, AccountingPeriod>
     */
    public function history(Group $group): Collection
    {
        return $group->accountingPeriods()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();
    }

    public function close(AccountingPeriod $period, User $actor): AccountingPeriod
    {
        if ($period->isClosed()) {
            return $period;
        }

        // A draft entry in the period would become unpostable the moment the
        // period closes, so it has to be resolved first.
        $drafts = JournalEntry::query()
            ->where('accounting_period_id', $period->getKey())
            ->where('status', JournalEntry::STATUS_DRAFT)
            ->count();

        if ($drafts > 0) {
            throw new RuntimeException(
                __('exceptions.period_has_draft_entries', ['period' => $period->label(), 'count' => $drafts])
            );
        }

        $period->forceFill([
            'status' => AccountingPeriod::STATUS_CLOSED,
            'closed_at' => now(),
            'closed_by' => $actor->getKey(),
        ])->save();

        $this->audit->record(
            AuditAction::PERIOD_CLOSED,
            group: $period->group,
            actor: $actor,
            object: $period,
            new: ['period' => $period->label()]
        );

        return $period;
    }

    /**
     * Reopening is deliberately available but audited: it is a correction of an
     * administrative mistake, not a routine operation.
     */
    public function reopen(AccountingPeriod $period, User $actor): AccountingPeriod
    {
        if (! $period->isClosed()) {
            return $period;
        }

        $period->forceFill([
            'status' => AccountingPeriod::STATUS_OPEN,
            'closed_at' => null,
            'closed_by' => null,
        ])->save();

        $this->audit->record(
            AuditAction::PERIOD_REOPENED,
            group: $period->group,
            actor: $actor,
            object: $period,
            new: ['period' => $period->label()]
        );

        return $period;
    }
}
