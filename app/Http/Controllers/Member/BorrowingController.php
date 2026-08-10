<?php

namespace App\Http\Controllers\Member;

use App\Domain\Loans\LoanService;
use App\Domain\Money\Decimal;
use App\Domain\Policies\PolicyService;
use App\Domain\Reports\ReportService;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Loan;
use App\Support\GroupContext;
use Illuminate\View\View;

/**
 * /u/{group}/borrowing — "can I borrow, what do I owe, and how do I pay it back?"
 *
 * One page for the whole borrowing relationship. Before this surface existed a
 * member had to hold three pages in their head — a loans list, a request form
 * and the generic transaction form with "loan repayment" selected — to answer a
 * question they think of as a single one.
 */
class BorrowingController extends Controller
{
    public function show(
        Group $group,
        LoanService $loans,
        PolicyService $policies,
        ReportService $reports,
        GroupContext $context,
    ): View {
        $member = $context->membership();
        $policy = $policies->activePolicy($group);
        $all = $reports->memberLoans($member);

        return view('member.borrowing', [
            'group' => $group,
            'policy' => $policy,
            'loanPolicy' => $policy?->toPolicyConfig()->loans(),
            // Asking for zero answers "what are my limits right now" without
            // committing to an amount; the same call enforces the real request.
            'eligibility' => $policy
                ? $loans->eligibility($member, Decimal::zero(), $group->functionalCurrency(), 1, $policy)
                : null,
            'outstanding' => $all->filter(fn (Loan $loan) => $loan->isOutstanding())->values(),
            'settled' => $all->filter(fn (Loan $loan) => ! $loan->isOutstanding())->values(),
        ]);
    }
}
