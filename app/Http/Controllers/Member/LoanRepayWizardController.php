<?php

namespace App\Http\Controllers\Member;

use App\Domain\Money\Decimal;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Loan;
use App\Models\Treasury;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Repay a loan" — a short wizard ending at the existing, unchanged
 * LoanController::repay. It has no administrative counterpart: an admin
 * records a repayment on someone's behalf from the dense inline form on the
 * shared loans/show.blade.php instead — see MemberSurface's 'loan.repay.start'
 * intent, which FundAdminSurface deliberately omits.
 */
class LoanRepayWizardController extends Controller
{
    public function treasury(Request $request, Group $group, Loan $loan): View|RedirectResponse
    {
        $this->authorize('repay', $loan);

        $treasuries = $this->treasuries($group, $loan);

        if ($treasuries->count() === 1) {
            return redirect()->route('u.borrowing.repay.amount', [
                $group,
                $loan,
                'treasury_id' => $treasuries->first()->getKey(),
            ]);
        }

        return view('member.wizard.repay-treasury', [
            'group' => $group,
            'loan' => $loan,
            'treasuries' => $treasuries,
        ]);
    }

    public function treasuryStore(Request $request, Group $group, Loan $loan): RedirectResponse
    {
        $this->authorize('repay', $loan);

        $data = $request->validate([
            'treasury_id' => ['required', 'integer'],
        ]);

        $treasury = $this->resolveTreasury($group, $loan, $data['treasury_id']);

        return redirect()->route('u.borrowing.repay.amount', [$group, $loan, 'treasury_id' => $treasury->getKey()]);
    }

    public function amount(Request $request, Group $group, Loan $loan): View
    {
        $this->authorize('repay', $loan);

        $outstanding = $loan->outstandingPrincipal();
        $scale = (int) config("fund.currencies.{$loan->currency}.scale", 2);

        return view('member.wizard.repay-amount', [
            'group' => $group,
            'loan' => $loan,
            'treasury' => $this->resolveTreasury($group, $loan, $request->query('treasury_id')),
            'outstanding' => $outstanding,
            'amount' => $request->query('amount', $outstanding->format($scale)),
        ]);
    }

    public function amountStore(Request $request, Group $group, Loan $loan): RedirectResponse
    {
        $this->authorize('repay', $loan);

        $treasury = $this->resolveTreasury($group, $loan, $request->input('treasury_id'));

        $data = $request->validate([
            'amount' => ['required', 'string', 'regex:/^\d+(\.\d+)?$/'],
        ]);

        return redirect()->route('u.borrowing.repay.review', [
            $group,
            $loan,
            'treasury_id' => $treasury->getKey(),
            'amount' => $data['amount'],
        ]);
    }

    public function review(Request $request, Group $group, Loan $loan): View
    {
        $this->authorize('repay', $loan);

        $treasury = $this->resolveTreasury($group, $loan, $request->query('treasury_id'));
        $amount = $request->query('amount');
        $scale = (int) config("fund.currencies.{$loan->currency}.scale", 2);

        return view('member.wizard.repay-review', [
            'group' => $group,
            'loan' => $loan,
            'treasury' => $treasury,
            'amount' => $amount,
            'amountFormatted' => Decimal::of($amount)->format($scale).' '.$loan->currency,
            'today' => now()->toDateString(),
        ]);
    }

    /**
     * Repayments only make sense into a treasury holding the loan's own
     * currency — the same filter LoanController::show already applies for
     * the administrator's inline form.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Treasury>
     */
    private function treasuries(Group $group, Loan $loan)
    {
        return $group->treasuries()
            ->where('status', Treasury::STATUS_ACTIVE)
            ->where('currency', $loan->currency)
            ->orderBy('name')
            ->get();
    }

    private function resolveTreasury(Group $group, Loan $loan, mixed $treasuryId): Treasury
    {
        return $group->treasuries()
            ->where('status', Treasury::STATUS_ACTIVE)
            ->where('currency', $loan->currency)
            ->findOrFail($treasuryId);
    }
}
