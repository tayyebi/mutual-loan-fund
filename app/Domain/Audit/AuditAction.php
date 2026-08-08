<?php

namespace App\Domain\Audit;

/**
 * The catalogue of material administrative actions that must leave a trace.
 */
final class AuditAction
{
    public const GROUP_CREATED = 'group.created';

    public const MEMBER_REQUESTED = 'member.requested';
    public const MEMBER_APPROVED = 'member.approved';
    public const MEMBER_REJECTED = 'member.rejected';
    public const MEMBER_SUSPENDED = 'member.suspended';
    public const MEMBER_REINSTATED = 'member.reinstated';
    public const MEMBER_ROLE_CHANGED = 'member.role_changed';

    public const WALLET_ADDED = 'wallet.added';
    public const WALLET_CHANGED = 'wallet.changed';

    public const TREASURY_CREATED = 'treasury.created';
    public const TREASURY_UPDATED = 'treasury.updated';
    public const TREASURY_RECONCILED = 'treasury.reconciled';

    public const TRANSACTION_SUBMITTED = 'transaction.submitted';
    public const TRANSACTION_VERIFIED = 'transaction.verified';
    public const TRANSACTION_REJECTED = 'transaction.rejected';
    public const TRANSACTION_CHAIN_VERIFIED = 'transaction.chain_verified';

    public const LOAN_REQUESTED = 'loan.requested';
    public const LOAN_APPROVED = 'loan.approved';
    public const LOAN_REJECTED = 'loan.rejected';
    public const LOAN_DISBURSED = 'loan.disbursed';
    public const LOAN_CANCELLED = 'loan.cancelled';

    public const EXCHANGE_RATE_ENTERED = 'exchange_rate.entered';

    public const JOURNAL_POSTED = 'journal.posted';
    public const JOURNAL_REVERSED = 'journal.reversed';
    public const ADJUSTMENT_CREATED = 'adjustment.created';

    public const PERIOD_CLOSED = 'accounting_period.closed';
    public const PERIOD_REOPENED = 'accounting_period.reopened';

    public const ACCOUNT_CREATED = 'account.created';
    public const ACCOUNT_UPDATED = 'account.updated';
    public const COST_CENTER_CREATED = 'cost_center.created';
    public const COST_CENTER_UPDATED = 'cost_center.updated';

    public const POLICY_CREATED = 'policy.created';
    public const POLICY_UPDATED = 'policy.updated';
    public const POLICY_PUBLISHED = 'policy.published';
    public const POLICY_DELETED = 'policy.deleted';
    public const POLICY_SUPERSEDED = 'policy.superseded';
}
