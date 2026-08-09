<?php

namespace Tests\Feature;

use App\Domain\Money\Decimal;
use App\Domain\Transactions\TransactionService;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The authenticated user's own corner under /p/: account, personal activity
 * and password changes.
 */
class PersonalAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_away_from_the_personal_area(): void
    {
        foreach (['p.home', 'p.transactions', 'p.password.edit'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_a_member_can_view_their_account(): void
    {
        $user = $this->user('Ali');

        $this->actingAs($user)
            ->get(route('p.home'))
            ->assertOk()
            ->assertSee('Ali')
            ->assertSee($user->email);
    }

    public function test_a_member_sees_only_transactions_in_which_they_are_the_member(): void
    {
        $admin = $this->user('Admin');
        $ali = $this->user('Ali');
        $betty = $this->user('Betty');
        $fund = $this->fund($admin);
        $aliMember = $this->member($fund, $ali, $admin);
        $bettyMember = $this->member($fund, $betty, $admin);
        $treasury = $this->treasury($fund, $admin);

        $transactions = app(TransactionService::class);

        $aliTx = $transactions->submitContribution($aliMember, [
            'treasury' => $treasury,
            'amount' => Decimal::of('500'),
            'currency' => 'USDT',
            'occurred_on' => Carbon::today(),
        ], $ali);

        $transactions->submitContribution($bettyMember, [
            'treasury' => $treasury,
            'amount' => Decimal::of('700'),
            'currency' => 'USDT',
            'occurred_on' => Carbon::today(),
        ], $betty);

        $this->actingAs($ali)
            ->get(route('p.transactions'))
            ->assertOk()
            ->assertSee(route('g.transactions.show', [$fund, $aliTx]))
            ->assertSee($fund->name);

        $this->assertSame(1, Transaction::query()
            ->whereHas('member', fn ($q) => $q->where('user_id', $ali->getKey()))
            ->count());
    }

    public function test_a_member_can_change_their_password(): void
    {
        $user = $this->user('Ali');

        $this->actingAs($user)
            ->put(route('p.password.update'), [
                'current_password' => 'password-for-tests',
                'password' => 'a-brand-new-password-123',
                'password_confirmation' => 'a-brand-new-password-123',
            ])
            ->assertRedirect(route('p.home'));

        $this->assertTrue(
            $user->refresh()->password === 'a-brand-new-password-123'
                ? false
                : app('hash')->check('a-brand-new-password-123', $user->password)
        );
    }

    public function test_a_member_cannot_change_password_with_a_wrong_current_password(): void
    {
        $user = $this->user('Ali');
        $old = $user->password;

        $this->actingAs($user)
            ->put(route('p.password.update'), [
                'current_password' => 'not-the-right-one',
                'password' => 'a-brand-new-password-123',
                'password_confirmation' => 'a-brand-new-password-123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertSame($old, $user->fresh()->password);
    }
}
