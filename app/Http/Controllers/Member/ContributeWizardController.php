<?php

namespace App\Http\Controllers\Member;

use App\Domain\Money\Decimal;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Treasury;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Pay money in" — a short wizard, one question per page, ending at the
 * existing, unchanged TransactionController::store. Every step here only
 * collects what that action already accepts; no business rule is duplicated
 * or reimplemented.
 */
class ContributeWizardController extends Controller
{
    public function treasury(Request $request, Group $group): View|RedirectResponse
    {
        $treasuries = $this->activeTreasuries($group);

        if ($treasuries->count() === 1) {
            return redirect()->route('u.money.contribute.amount', [
                $group,
                'treasury_id' => $treasuries->first()->getKey(),
            ]);
        }

        return view('member.wizard.contribute-treasury', [
            'group' => $group,
            'treasuries' => $treasuries,
        ]);
    }

    public function treasuryStore(Request $request, Group $group): RedirectResponse
    {
        $data = $request->validate([
            'treasury_id' => ['required', 'integer'],
        ]);

        $treasury = $this->resolveTreasury($group, $data['treasury_id']);

        return redirect()->route('u.money.contribute.amount', [$group, 'treasury_id' => $treasury->getKey()]);
    }

    public function amount(Request $request, Group $group): View
    {
        return view('member.wizard.contribute-amount', [
            'group' => $group,
            'treasury' => $this->resolveTreasury($group, $request->query('treasury_id')),
            'amount' => $request->query('amount'),
        ]);
    }

    public function amountStore(Request $request, Group $group): RedirectResponse
    {
        $treasury = $this->resolveTreasury($group, $request->input('treasury_id'));

        $data = $request->validate([
            'amount' => ['required', 'string', 'regex:/^\d+(\.\d+)?$/'],
        ]);

        return redirect()->route('u.money.contribute.review', [
            $group,
            'treasury_id' => $treasury->getKey(),
            'amount' => $data['amount'],
        ]);
    }

    public function review(Request $request, Group $group): View
    {
        $treasury = $this->resolveTreasury($group, $request->query('treasury_id'));
        $amount = $request->query('amount');
        $scale = (int) config("fund.currencies.{$treasury->currency}.scale", 2);

        return view('member.wizard.contribute-review', [
            'group' => $group,
            'treasury' => $treasury,
            'amount' => $amount,
            'amountFormatted' => Decimal::of($amount)->format($scale).' '.$treasury->currency,
            'today' => now()->toDateString(),
        ]);
    }

    private function activeTreasuries(Group $group)
    {
        return $group->treasuries()->where('status', Treasury::STATUS_ACTIVE)->orderBy('name')->get();
    }

    private function resolveTreasury(Group $group, mixed $treasuryId): Treasury
    {
        return $group->treasuries()
            ->where('status', Treasury::STATUS_ACTIVE)
            ->findOrFail($treasuryId);
    }
}
