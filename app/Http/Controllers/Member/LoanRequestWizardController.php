<?php

namespace App\Http\Controllers\Member;

use App\Domain\Loans\LoanService;
use App\Domain\Money\Decimal;
use App\Domain\Policies\PolicyService;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Treasury;
use App\Support\GroupContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Request a loan" — a short wizard ending at the existing, unchanged
 * LoanController::store. Eligibility is the same LoanService::eligibility()
 * call the old single-page form used, just shown one step at a time so a
 * member sees where they stand before being asked to commit to anything.
 */
class LoanRequestWizardController extends Controller
{
    public function amount(Request $request, Group $group, PolicyService $policies): View
    {
        $policy = $policies->requireActivePolicy($group);
        $loanPolicy = $policy->toPolicyConfig()->loans();
        $currencies = $this->currencies($group);

        return view('member.wizard.loan-amount', [
            'group' => $group,
            'policy' => $policy,
            'loanPolicy' => $loanPolicy,
            'currencies' => $currencies,
            'amount' => $request->query('amount', (string) ($loanPolicy->maximumAmount() ?? Decimal::zero())),
            'currency' => $request->query('currency', $currencies[0] ?? $group->functionalCurrency()),
        ]);
    }

    public function amountStore(Request $request, Group $group): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'string', 'regex:/^\d+(\.\d+)?$/'],
            'currency' => ['required', 'string', 'in:'.implode(',', array_keys((array) config('fund.currencies')))],
        ]);

        return redirect()->route('u.borrowing.request.term', [
            $group,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
        ]);
    }

    public function term(
        Request $request,
        Group $group,
        PolicyService $policies,
        LoanService $loans,
        GroupContext $context
    ): View {
        $policy = $policies->requireActivePolicy($group);
        $loanPolicy = $policy->toPolicyConfig()->loans();

        $amount = Decimal::parse($request->query('amount')) ?? Decimal::zero();
        $currency = $request->query('currency');
        $term = (int) $request->query('term_months', $loanPolicy->maximumTermMonths());

        return view('member.wizard.loan-term', [
            'group' => $group,
            'amount' => $request->query('amount'),
            'currency' => $currency,
            'term' => $term,
            'termOptions' => $this->termOptions($loanPolicy->minimumTermMonths(), $loanPolicy->maximumTermMonths()),
            'eligibility' => $loans->eligibility($context->membership(), $amount, $currency, $term, $policy),
        ]);
    }

    public function termStore(Request $request, Group $group): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'string', 'regex:/^\d+(\.\d+)?$/'],
            'currency' => ['required', 'string', 'in:'.implode(',', array_keys((array) config('fund.currencies')))],
            'term_months' => ['required', 'integer', 'min:1', 'max:600'],
        ]);

        return redirect()->route('u.borrowing.request.review', [
            $group,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'term_months' => $data['term_months'],
        ]);
    }

    public function review(
        Request $request,
        Group $group,
        PolicyService $policies,
        LoanService $loans,
        GroupContext $context
    ): View {
        $policy = $policies->requireActivePolicy($group);
        $loanPolicy = $policy->toPolicyConfig()->loans();
        $amount = Decimal::parse($request->query('amount')) ?? Decimal::zero();
        $currency = $request->query('currency');
        $term = (int) $request->query('term_months');
        $scale = (int) config("fund.currencies.{$currency}.scale", 2);

        return view('member.wizard.loan-review', [
            'group' => $group,
            'policy' => $policy,
            'loanPolicy' => $loanPolicy,
            'amount' => $request->query('amount'),
            'amountFormatted' => $amount->format($scale).' '.$currency,
            'currency' => $currency,
            'term' => $term,
            'eligibility' => $loans->eligibility($context->membership(), $amount, $currency, $term, $policy),
        ]);
    }

    /**
     * Loans can only be made in a currency the fund actually holds.
     *
     * @return list<string>
     */
    private function currencies(Group $group): array
    {
        return $group->treasuries()
            ->where('status', Treasury::STATUS_ACTIVE)
            ->pluck('currency')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * A curated set of term choices rather than a free-typed number — every
     * month when the policy's range is short, otherwise common milestones.
     *
     * @return list<int>
     */
    private function termOptions(int $min, int $max): array
    {
        if ($max - $min <= 24) {
            return range($min, $max);
        }

        $milestones = [1, 3, 6, 9, 12, 18, 24, 36, 48, 60, 84, 120];
        $options = array_values(array_unique(array_filter(
            [$min, ...$milestones, $max],
            fn ($months) => $months >= $min && $months <= $max
        )));
        sort($options);

        return $options;
    }
}
