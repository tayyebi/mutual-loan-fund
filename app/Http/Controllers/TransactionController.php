<?php

namespace App\Http\Controllers;

use App\Domain\Access\SurfaceRoute;
use App\Domain\Money\Decimal;
use App\Domain\Policies\Exceptions\PolicyViolationException;
use App\Domain\Reports\ReportService;
use App\Domain\Transactions\ReceiptService;
use App\Domain\Transactions\TransactionService;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Group;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\Treasury;
use App\Support\GroupContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class TransactionController extends Controller
{
    public function index(Request $request, Group $group): View
    {
        $filters = $request->only(['type', 'status', 'member_id', 'treasury_id', 'currency', 'from', 'to']);

        $transactions = Transaction::query()
            ->forGroup($group)
            ->with(['member.user', 'treasury', 'loan'])
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['member_id'] ?? null, fn ($q, $member) => $q->where('member_id', $member))
            ->when($filters['treasury_id'] ?? null, fn ($q, $treasury) => $q->where('treasury_id', $treasury))
            ->when($filters['currency'] ?? null, fn ($q, $currency) => $q->where('currency', $currency))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('occurred_on', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('occurred_on', '<=', $to))
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->paginate(40)
            ->withQueryString();

        return view('transactions.index', [
            'group' => $group,
            'transactions' => $transactions,
            'filters' => $filters,
            'members' => $group->activeMemberships()->with('user')->get(),
            'treasuries' => $group->treasuries()->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request, Group $group, ReportService $reports, GroupContext $context): View
    {
        return view('transactions.create', [
            'group' => $group,
            'type' => $request->query('type', Transaction::TYPE_CONTRIBUTION),
            'treasuries' => $group->treasuries()->where('status', Treasury::STATUS_ACTIVE)->orderBy('name')->get(),
            'loans' => $reports->repayableLoans($context->membership()),
            /*
            | Whether to offer the administrative movement types — transfers,
            | exchanges, standalone fees — decided by *surface*, not by role. An
            | administrator paying their own money in at /u/{group}/money/contribute
            | is contributing like anyone else and should be shown the simple
            | form. The server-side guard in recordAdministrative() still checks
            | the role, which is the check that actually protects anything.
            */
            'isAdmin' => SurfaceRoute::serves('transaction.verify'),
        ]);
    }

    public function store(
        StoreTransactionRequest $request,
        Group $group,
        TransactionService $transactions,
        ReceiptService $receipts,
        GroupContext $context,
    ): RedirectResponse {
        $data = $request->validated();
        $treasury = $group->treasuries()->findOrFail($data['treasury_id']);
        $amount = Decimal::of($data['amount']);
        $occurredOn = Carbon::parse($data['occurred_on']);

        $common = [
            'treasury' => $treasury,
            'amount' => $amount,
            'currency' => $treasury->currency,
            'occurred_on' => $occurredOn,
            'reference' => $data['reference'] ?? null,
            'description' => $data['description'] ?? null,
            'network' => $treasury->network,
            'tx_hash' => $data['tx_hash'] ?? null,
            'from_address' => $data['from_address'] ?? null,
        ];

        try {
            $transaction = match ($data['type']) {
                Transaction::TYPE_CONTRIBUTION => $transactions->submitContribution(
                    $context->membership(),
                    $common,
                    $request->user()
                ),
                Transaction::TYPE_LOAN_REPAYMENT => $transactions->submitRepayment(
                    $this->repayableLoan($group, (int) $data['loan_id'], $context),
                    $common,
                    $request->user()
                ),
                default => $this->recordAdministrative($group, $data, $treasury, $transactions, $request),
            };
        } catch (PolicyViolationException $e) {
            return back()->withInput()->withErrors([$e->field ?? 'amount' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['tx_hash' => $e->getMessage()]);
        }

        if ($request->hasFile('receipt')) {
            $receipts->attach($transaction, $request->file('receipt'), $request->user());
        }

        // Evidence is checked immediately so the administrator sees the chain's
        // answer next to the claim.
        if ($transaction->hasChainEvidence()) {
            $transactions->runChainVerification($transaction);
        }

        return redirect()
            ->route(SurfaceRoute::name('transaction.show'), [$group, $transaction])
            ->with('status', 'Submitted. It becomes financially effective once an administrator verifies it.');
    }

    public function show(Group $group, Transaction $transaction, TransactionService $transactions): View
    {
        $this->authorize('view', $transaction);

        return view('transactions.show', [
            'group' => $group,
            'transaction' => $transaction->load([
                'member.user', 'treasury', 'counterTreasury', 'loan', 'receipts',
                'policyVersion', 'creator', 'verifier',
                'journalEntries.lines.account', 'journalEntries.lines.costCenter',
            ]),
            'needsAdminVerification' => $transactions->needsAdminVerification($transaction),
        ]);
    }

    public function verify(Group $group, Transaction $transaction, TransactionService $transactions): RedirectResponse
    {
        $this->authorize('verify', $transaction);

        try {
            $transactions->verify($transaction, request()->user());
        } catch (Throwable $e) {
            return back()->withErrors(['transaction' => $e->getMessage()]);
        }

        return back()->with('status', 'Verified and posted to the ledger.');
    }

    public function reject(Request $request, Group $group, Transaction $transaction, TransactionService $transactions): RedirectResponse
    {
        $this->authorize('reject', $transaction);

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $transactions->reject($transaction, $request->user(), $data['reason']);

        return back()->with('status', 'Transaction rejected.');
    }

    public function chainCheck(Group $group, Transaction $transaction, TransactionService $transactions): RedirectResponse
    {
        $result = $transactions->runChainVerification($transaction);

        return back()->with('status', $result->confirmed
            ? 'The chain confirms this transfer.'
            : ($result->reason ?? 'The chain could not confirm this transfer.'));
    }

    public function storeReceipt(Request $request, Group $group, Transaction $transaction, ReceiptService $receipts): RedirectResponse
    {
        $this->authorize('attachReceipt', $transaction);

        $request->validate([
            'receipt' => [
                'required',
                'file',
                'mimes:'.implode(',', (array) config('fund.receipts.mimes')),
                'max:'.config('fund.receipts.max_kilobytes'),
            ],
        ]);

        $receipts->attach($transaction, $request->file('receipt'), $request->user());

        return back()->with('status', 'Receipt attached.');
    }

    private function repayableLoan(Group $group, int $loanId, GroupContext $context): Loan
    {
        $loan = Loan::query()->forGroup($group)->findOrFail($loanId);

        // A member may only repay their own loan; an administrator may record a
        // repayment on anyone's behalf.
        if (! $context->isAdmin() && ! $context->owns($loan->member)) {
            abort(403);
        }

        return $loan;
    }

    /**
     * Transfers, exchanges and standalone fees are administrative movements.
     *
     * @param  array<string, mixed>  $data
     */
    private function recordAdministrative(
        Group $group,
        array $data,
        Treasury $treasury,
        TransactionService $transactions,
        Request $request,
    ): Transaction {
        if (! app(GroupContext::class)->isAdmin()) {
            abort(403, __('exceptions.restricted_to_admins_treasury_movements'));
        }

        $counterTreasury = isset($data['counter_treasury_id'])
            ? $group->treasuries()->find($data['counter_treasury_id'])
            : null;

        return $transactions->record($group, [
            'treasury_id' => $treasury->getKey(),
            'counter_treasury_id' => $counterTreasury?->getKey(),
            'type' => $data['type'],
            'direction' => $data['type'] === Transaction::TYPE_FEE
                ? Transaction::DIRECTION_OUT
                : Transaction::DIRECTION_INTERNAL,
            'amount' => Decimal::of($data['amount'])->toString(),
            'currency' => $treasury->currency,
            'counter_amount' => isset($data['counter_amount'])
                ? Decimal::of($data['counter_amount'])->toString()
                : null,
            'counter_currency' => $counterTreasury?->currency,
            'fee_amount' => isset($data['fee_amount']) ? Decimal::of($data['fee_amount'])->toString() : null,
            'fee_currency' => isset($data['fee_amount']) ? $treasury->currency : null,
            'occurred_on' => Carbon::parse($data['occurred_on']),
            'reference' => $data['reference'] ?? null,
            'description' => $data['description'] ?? null,
            'network' => $treasury->network,
            'tx_hash' => $data['tx_hash'] ?? null,
        ], $request->user());
    }
}
