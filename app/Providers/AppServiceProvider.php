<?php

namespace App\Providers;

use App\Domain\Blockchain\ChainVerifier;
use App\Domain\Blockchain\ManualVerifier;
use App\Domain\Blockchain\TronGridVerifier;
use App\Domain\Loans\Listeners\ApplyLoanMovement;
use App\Domain\Transactions\Events\TransactionVerified;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\GroupPolicy as PolicyVersion;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Policies\GroupPolicy;
use App\Policies\LoanPolicy;
use App\Policies\PolicyVersionPolicy;
use App\Policies\TransactionPolicy;
use App\Policies\WalletPolicy;
use App\Support\GroupContext;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One context per request, populated by ResolveGroupContext.
        $this->app->scoped(GroupContext::class);

        $this->app->bind(ChainVerifier::class, fn () => match (config('fund.blockchain.verifier')) {
            'trongrid' => new TronGridVerifier,
            default => new ManualVerifier,
        });
    }

    public function boot(): void
    {
        // Policies are registered explicitly: several model names have a
        // same-named class in the domain layer, so discovery by convention would
        // be ambiguous to read even where it works.
        Gate::policy(Group::class, GroupPolicy::class);
        Gate::policy(PolicyVersion::class, PolicyVersionPolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(Loan::class, LoanPolicy::class);
        Gate::policy(Wallet::class, WalletPolicy::class);

        Event::listen(TransactionVerified::class, ApplyLoanMovement::class);

        // Exchange rates are global data, so the ability to enter them is not
        // tied to one fund: anyone who administers a fund may maintain them.
        Gate::define('manage-exchange-rates', fn (User $user) => $user->memberships()
            ->where('role', GroupMembership::ROLE_ADMIN)
            ->whereIn('status', [GroupMembership::STATUS_APPROVED, GroupMembership::STATUS_ACTIVE])
            ->exists());

        // Behind the reverse proxy the app only ever speaks HTTPS in production.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
