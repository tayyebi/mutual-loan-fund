<?php

namespace App\Http\Controllers;

use App\Domain\Wallets\WalletService;
use App\Http\Requests\StoreWalletRequest;
use App\Models\Group;
use App\Models\Wallet;
use App\Support\GroupContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class WalletController extends Controller
{
    public function index(Group $group, GroupContext $context): View
    {
        $membership = $context->membership();

        return view('wallets.index', [
            'group' => $group,
            'wallets' => $membership->wallets()->orderBy('label')->get(),
            // Administrators need to recognise incoming transfers, so they can
            // see the group's registered addresses.
            'groupWallets' => $context->isAdmin()
                ? $group->wallets()->with('member.user')->orderBy('id')->get()
                : collect(),
            'networks' => config('fund.networks'),
            'currencies' => config('fund.currencies'),
        ]);
    }

    public function store(StoreWalletRequest $request, Group $group, WalletService $wallets, GroupContext $context): RedirectResponse
    {
        try {
            $wallets->register($context->membership(), $request->validated(), $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['address' => $e->getMessage()]);
        }

        return back()->with('status', 'Wallet registered.');
    }

    public function update(Request $request, Group $group, Wallet $wallet, WalletService $wallets): RedirectResponse
    {
        $this->authorize('update', $wallet);

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:80'],
            'status' => ['required', 'in:'.Wallet::STATUS_ACTIVE.','.Wallet::STATUS_INACTIVE],
        ]);

        $wallets->update($wallet, $data, $request->user());

        return back()->with('status', 'Wallet updated.');
    }
}
