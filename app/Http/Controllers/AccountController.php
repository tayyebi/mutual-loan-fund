<?php

namespace App\Http\Controllers;

use App\Domain\Accounting\LedgerBalances;
use App\Models\Account;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Group $group, LedgerBalances $balances): View
    {
        $accounts = $group->accounts()->orderBy('code')->get();
        $trial = $balances->trialBalance($group)->keyBy(fn (array $row) => $row['account']->getKey());

        return view('accounts.index', [
            'group' => $group,
            'accounts' => $accounts,
            'balances' => $trial,
            'currency' => $group->functionalCurrency(),
        ]);
    }

    /**
     * The general ledger for one account: every posted line against it.
     */
    public function show(Request $request, Group $group, Account $account, LedgerBalances $balances): View
    {
        $from = $request->query('from') ? Carbon::parse($request->query('from')) : null;
        $to = $request->query('to') ? Carbon::parse($request->query('to')) : null;

        return view('accounts.show', [
            'group' => $group,
            'account' => $account,
            'lines' => $balances->generalLedger($account, $from, $to),
            'balance' => $balances->accountBalance($account, $to),
            'nativeBalances' => $balances->accountNativeBalances($account, $to),
            'currency' => $group->functionalCurrency(),
            'filters' => $request->only(['from', 'to']),
        ]);
    }
}
