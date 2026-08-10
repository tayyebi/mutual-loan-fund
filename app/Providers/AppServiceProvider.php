<?php

namespace App\Providers;

use App\Domain\Access\NavigationBuilder;
use App\Domain\Access\SurfaceRoute;
use App\Domain\Blockchain\ChainVerifier;
use App\Domain\Blockchain\ManualVerifier;
use App\Domain\Blockchain\TronGridVerifier;
use App\Domain\Loans\Listeners\ApplyLoanMovement;
use App\Domain\Transactions\Events\TransactionVerified;
use App\Models\Group;
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
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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

        /*
        | The layout's navigation is derived from App\Domain\Access rather than
        | hand-written in Blade. It is resolved by a composer rather than by
        | middleware because it depends on GroupContext, which route middleware
        | ('group') only populates after the global stack has already run —
        | a composer fires at render time, by which point the fund is known.
        */
        View::composer('layouts.app', function ($view) {
            $view->with('navigation', app(NavigationBuilder::class)->build(request()->user()));
        });

        /*
        | Views shared by two surfaces link by intent rather than by route name:
        |
        |     <a href="@surface('transaction.show', $group, $transaction)">
        |
        | so the same template sends a member to /u and an administrator to /g.
        | @surfaces() is the boolean form, for actions that exist on only one.
        */
        Blade::directive(
            'surface',
            fn (string $expression) => "<?php echo e(\App\Domain\Access\SurfaceRoute::to({$expression})); ?>",
        );

        Blade::if(
            'surfaces',
            fn (string $intent) => SurfaceRoute::serves($intent),
        );

        // Exchange rates are global data, shared by every fund, so maintaining
        // them is a platform responsibility rather than any one fund's.
        Gate::define('manage-exchange-rates', fn (User $user) => $user->isSystemAdmin());

        // Behind the reverse proxy the app only ever speaks HTTPS in production.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
