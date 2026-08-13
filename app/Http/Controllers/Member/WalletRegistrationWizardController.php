<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Register a wallet" — a short wizard ending at the existing, unchanged
 * WalletController::store (via StoreWalletRequest). Every step here only
 * collects what that action already accepts; no business rule is duplicated.
 */
class WalletRegistrationWizardController extends Controller
{
    public function network(Request $request, Group $group): View
    {
        return view('member.wizard.wallet-network', [
            'group' => $group,
            'networks' => config('fund.networks'),
            'currencies' => $this->currencies(),
            'network' => $request->query('network'),
            'currency' => $request->query('currency', $request->user()->preferred_currency),
        ]);
    }

    public function networkStore(Request $request, Group $group): RedirectResponse
    {
        $data = $request->validate([
            'network' => ['required', 'string', 'in:'.implode(',', array_keys((array) config('fund.networks')))],
            'currency' => ['required', 'string', 'in:'.implode(',', array_keys($this->currencies()))],
        ]);

        return redirect()->route('u.wallets.register.address', [
            $group,
            'network' => $data['network'],
            'currency' => $data['currency'],
        ]);
    }

    public function address(Request $request, Group $group): View
    {
        return view('member.wizard.wallet-address', [
            'group' => $group,
            'network' => $request->query('network'),
            'currency' => $request->query('currency'),
        ]);
    }

    /**
     * Wallets pay real money rails, never the synthetic gold accounting unit.
     *
     * @return array<string, mixed>
     */
    private function currencies(): array
    {
        return collect((array) config('fund.currencies'))
            ->except(config('fund.gold_unit'))
            ->all();
    }
}
