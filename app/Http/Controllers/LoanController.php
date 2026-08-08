<?php

namespace App\Http\Controllers;

use App\Domain\Loans\LoanService;
use App\Domain\Money\Decimal;
use App\Domain\Policies\Exceptions\PolicyViolationException;
use App\Domain\Policies\PolicyService;
use App\Domain\Transactions\TransactionService;
use App\Models\Group;
use App\Models\Loan;
use App\Models\Treasury;
use App\Support\GroupContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use RuntimeException;

class LoanController extends Controller
{
    public function index(Group $group, GroupContext $context): View
    {
        $loans = Loan::query()
            ->forGroup($group)
            ->with(['member.user', 'policyVersion'])
            // A member sees their own loans; an administrator sees the fund's.
            ->unless($context->isAdmin(), fn ($q) => $q->where('member_id', $context->membership()->getKey()))
            ->orderByDesc('id')
            ->get();

        return view('loans.index', [
            'group' => $group,
            'loans' => $loans,
            'isAdmin' => $context->isAdmin(),
        ]);
    }

    public function create(Request $request, Group $group, LoanService $loans, PolicyService $policies, GroupContext $context): View
    {
        $policy = $policies->requireActivePolicy($group);
        $loanPolicy = $policy->toPolicyConfig()->loans();

        $currency = $request->query('currency', $group->functionalCurrency());
        $amount = Decimal::parse($request->query('amount')) ?? $loanPolicy->maximumAmount() ?? Decimal::zero();
        $term = (int) $request->query('term_months', $loanPolicy->maximumTermMonths());

        return view('loans.create', [
            'group' => $group,
            'policy' => $policy,
            'loanPolicy' => $loanPolicy,
            // Shown before submission, and enforced again on the server: the two
            // come from the same call, so they cannot disagree.
            'eligibility' => $loans->eligibility($context->membership(), $amount, $currency, $term, $policy),
            'currencies' => $this->loanCurrencies($group),
            'amount' => $amount,
            'term' => $term,
            'currency' => $currency,
        ]);
    }

    public function store(Request $request, Group $group, LoanService $loans, GroupContext $context): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'string', 'regex:/^\d+(\.\d+)?$/'],
            'currency' => ['required', 'string', 'in:'.implode(',', array_keys((array) config('fund.currencies')))],
            'term_months' => ['required', 'integer', 'min:1', 'max:600'],
            'purpose' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $loan = $loans->request($context->membership(), [
                'amount' => Decimal::of($data['amount']),
                'currency' => $data['currency'],
                'term_months' => (int) $data['term_months'],
                'purpose' => $data['purpose'] ?? null,
            ], $request->user());
        } catch (PolicyViolationException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()
            ->route('g.loans.show', [$group, $loan])
            ->with('status', "Loan {$loan->reference} requested under policy v{$loan->policyVersion->version}.");
    }

    public function show(Group $group, Loan $loan, LoanService $loans): View
    {
        $this->authorize('view', $loan);

        return view('loans.show', [
            'group' => $group,
            'loan' => $loan->load([
                'member.user', 'costCenter', 'policyVersion', 'installments',
                'transactions.treasury', 'approver',
            ]),
            'loanPolicy' => $loan->policyVersion->toPolicyConfig()->loans(),
            'outstanding' => $loan->outstandingPrincipal(),
            'treasuries' => $group->treasuries()
                ->where('status', Treasury::STATUS_ACTIVE)
                ->where('currency', $loan->currency)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function approve(Request $request, Group $group, Loan $loan, LoanService $loans): RedirectResponse
    {
        $this->authorize('approve', $loan);

        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        try {
            $loans->approve($loan, $request->user(), $data['note'] ?? null);
        } catch (PolicyViolationException|RuntimeException $e) {
            return back()->withErrors(['loan' => $e->getMessage()]);
        }

        return back()->with('status', "Loan {$loan->reference} approved under policy v{$loan->policyVersion->version}.");
    }

    public function reject(Request $request, Group $group, Loan $loan, LoanService $loans): RedirectResponse
    {
        $this->authorize('approve', $loan);

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        try {
            $loans->reject($loan, $request->user(), $data['reason']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['loan' => $e->getMessage()]);
        }

        return back()->with('status', "Loan {$loan->reference} rejected.");
    }

    public function disburse(Request $request, Group $group, Loan $loan, LoanService $loans): RedirectResponse
    {
        $this->authorize('disburse', $loan);

        $data = $request->validate([
            'treasury_id' => ['required', 'integer'],
            'occurred_on' => ['required', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:200'],
            'tx_hash' => ['nullable', 'string', 'max:120'],
        ]);

        $treasury = $group->treasuries()->findOrFail($data['treasury_id']);

        try {
            $transaction = $loans->recordDisbursement(
                $loan,
                $treasury,
                Carbon::parse($data['occurred_on']),
                $request->user(),
                $data['reference'] ?? null,
                $data['tx_hash'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['loan' => $e->getMessage()]);
        }

        return redirect()
            ->route('g.transactions.show', [$group, $transaction])
            ->with('status', 'Disbursement recorded. Verify it to post the receivable and reduce the treasury.');
    }

    public function repay(Request $request, Group $group, Loan $loan, TransactionService $transactions): RedirectResponse
    {
        $this->authorize('repay', $loan);

        $data = $request->validate([
            'treasury_id' => ['required', 'integer'],
            'amount' => ['required', 'string', 'regex:/^\d+(\.\d+)?$/'],
            'occurred_on' => ['required', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:200'],
            'tx_hash' => ['nullable', 'string', 'max:120'],
        ]);

        $treasury = $group->treasuries()->findOrFail($data['treasury_id']);

        try {
            $transaction = $transactions->submitRepayment($loan, [
                'treasury' => $treasury,
                'amount' => Decimal::of($data['amount']),
                'currency' => $treasury->currency,
                'occurred_on' => Carbon::parse($data['occurred_on']),
                'reference' => $data['reference'] ?? null,
                'tx_hash' => $data['tx_hash'] ?? null,
                'network' => $treasury->network,
            ], $request->user());
        } catch (PolicyViolationException|RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()
            ->route('g.transactions.show', [$group, $transaction])
            ->with('status', 'Repayment submitted. It affects the loan once verified.');
    }

    public function cancel(Group $group, Loan $loan, LoanService $loans): RedirectResponse
    {
        $this->authorize('cancel', $loan);

        try {
            $loans->cancel($loan, request()->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['loan' => $e->getMessage()]);
        }

        return back()->with('status', "Loan {$loan->reference} cancelled.");
    }

    /**
     * Loans can only be made in a currency the fund actually holds.
     *
     * @return array<int, string>
     */
    private function loanCurrencies(Group $group): array
    {
        return $group->treasuries()
            ->where('status', Treasury::STATUS_ACTIVE)
            ->pluck('currency')
            ->unique()
            ->values()
            ->all();
    }
}
