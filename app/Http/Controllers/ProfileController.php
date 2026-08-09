<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePasswordRequest;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The authenticated user's own corner of the application, served under /p/.
 *
 * Nothing here is tenant-scoped: these pages describe the account itself — its
 * password, and the transactions in which it was a member — across every fund
 * it belongs to.
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('profile.home', [
            'user' => $user,
            'memberships' => $user->memberships()->with('group')->get(),
        ]);
    }

    /**
     * Every transaction in which this account is the member, newest first.
     * Administrative treasury movements belong to the fund, not to a member, so
     * they are not listed here.
     */
    public function transactions(Request $request): View
    {
        $transactions = Transaction::query()
            ->whereHas('member', fn ($query) => $query->where('user_id', $request->user()->getKey()))
            ->with(['member.user', 'treasury', 'loan', 'group'])
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->paginate(40)
            ->withQueryString();

        return view('profile.transactions', ['transactions' => $transactions]);
    }

    public function editPreferences(): View
    {
        return view('profile.preferences');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'preferred_locale' => ['nullable', Rule::in(array_keys(config('locales.available')))],
            'preferred_currency' => ['nullable', Rule::in(array_keys(config('fund.currencies')))],
            'timezone' => ['nullable', 'timezone'],
            'weekend_days' => ['nullable', 'array'],
            'weekend_days.*' => ['integer', 'between:0,6'],
        ]);

        $request->user()->update([
            'preferred_locale' => $data['preferred_locale'] ?? null,
            'preferred_currency' => $data['preferred_currency'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'weekend_days' => $data['weekend_days'] ?? null,
        ]);

        return redirect()
            ->route('p.home')
            ->with('status', 'Your preferences have been saved.');
    }

    public function editPassword(): View
    {
        return view('profile.password');
    }

    public function updatePassword(StorePasswordRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => $request->validated('password'),
        ])->save();

        return redirect()
            ->route('p.home')
            ->with('status', 'Your password has been changed.');
    }
}
