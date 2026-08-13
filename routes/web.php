<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountingPeriodController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\Fund\HubController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\Member\BorrowingController;
use App\Http\Controllers\Member\ContributeWizardController;
use App\Http\Controllers\Member\FundController as MemberFundController;
use App\Http\Controllers\Member\LoanRepayWizardController;
use App\Http\Controllers\Member\LoanRequestWizardController;
use App\Http\Controllers\Member\MoneyController;
use App\Http\Controllers\Member\OverviewController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\System\AuditController as SystemAuditController;
use App\Http\Controllers\System\DashboardController as SystemDashboardController;
use App\Http\Controllers\System\FundController as SystemFundController;
use App\Http\Controllers\System\UserController as SystemUserController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TreasuryController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

/*
| Server-rendered HTTP only: GET renders Blade, POST validates, authorises,
| runs one database transaction and redirects. No AJAX, no API surface.
|
| The URL space is partitioned by access level, one prefix per level, and each
| prefix is a complete experience rather than a filtered view of another:
|
|   /u/{group}  what a member wants — my money, my borrowing, this fund
|   /g/{group}  running one fund — verification, books, policy, audit
|   /s          running the platform — user and fund lifecycle
|
| The levels are cumulative, so a fund administrator uses /u for their own money
| and a system administrator who invests in a fund does the same. Reaching /u or
| /g always requires a real membership in that fund; being a system
| administrator grants nothing inside anyone's books.
|
| The declarative counterpart of this file is App\Domain\Access\AccessMap, which
| maps each level to its surface and navigation. A route added here that should
| be reachable from the nav needs one NavItem there.
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    /*
    | The cross-fund landing. It belongs to no surface: the user has not chosen
    | a fund yet, so no access level is in play.
    */
    Route::get('/', [HomeController::class, 'index'])->name('home');

    /*
    | The account's own corner: password, preferences and personal activity.
    | Nothing here is tenant-scoped and nothing here is an access level — every
    | authenticated account has exactly the same settings — which is why it sits
    | outside the three level prefixes rather than inside /u.
    */
    Route::prefix('p')->name('p.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('home');
        Route::get('transactions', [ProfileController::class, 'transactions'])->name('transactions');
        Route::get('password', [ProfileController::class, 'editPassword'])->name('password.edit');
        Route::put('password', [ProfileController::class, 'updatePassword'])->name('password.update');
        Route::get('preferences', [ProfileController::class, 'editPreferences'])->name('preferences.edit');
        Route::put('preferences', [ProfileController::class, 'updatePreferences'])->name('preferences.update');
    });

    /*
    | Platform operations: user and fund lifecycle across the whole application,
    | never any one fund's financial data. Restricted to accounts with
    | User::system_role = system_admin, checked directly on the user rather than
    | through GroupContext, since nothing here is tenant-scoped.
    */
    Route::prefix('s')->name('s.')->middleware('system.admin')->group(function () {
        Route::get('/', [SystemDashboardController::class, 'index'])->name('dashboard');

        Route::get('users', [SystemUserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [SystemUserController::class, 'show'])->name('users.show');
        Route::post('users/{user}/suspend', [SystemUserController::class, 'suspend'])->name('users.suspend');
        Route::post('users/{user}/reinstate', [SystemUserController::class, 'reinstate'])->name('users.reinstate');
        Route::post('users/{user}/promote', [SystemUserController::class, 'promote'])->name('users.promote');
        Route::post('users/{user}/demote', [SystemUserController::class, 'demote'])->name('users.demote');

        Route::get('funds', [SystemFundController::class, 'index'])->name('funds.index');
        Route::get('funds/{group}', [SystemFundController::class, 'show'])->name('funds.show');
        Route::post('funds/{group}/suspend', [SystemFundController::class, 'suspend'])->name('funds.suspend');
        Route::post('funds/{group}/reinstate', [SystemFundController::class, 'reinstate'])->name('funds.reinstate');

        Route::get('audit', [SystemAuditController::class, 'index'])->name('audit.index');
    });

    Route::get('/g/create', [GroupController::class, 'create'])->name('groups.create');
    Route::post('/g', [GroupController::class, 'store'])->name('groups.store');

    // Joining happens before membership exists, so these two sit outside the
    // group middleware and do their own checks.
    Route::get('/g/{group}/join', [GroupController::class, 'showJoin'])->name('groups.join');
    Route::post('/g/{group}/join', [GroupController::class, 'join']);

    Route::get('/exchange-rates', [ExchangeRateController::class, 'index'])->name('exchange-rates.index');
    Route::post('/exchange-rates', [ExchangeRateController::class, 'store'])->name('exchange-rates.store');

    /*
    |---------------------------------------------------------------------------
    | /u/{group} — the member surface
    |---------------------------------------------------------------------------
    | Every member of the fund, administrators included. Routes are named after
    | the member's intent, and the segments are the words they would use: money,
    | borrowing, activity, fund. Where the underlying work is identical to the
    | administrative one the same controller action serves both surfaces — the
    | difference is the URL the member arrives through and what the page shows
    | them, not a second implementation of contributing or repaying.
    */
    Route::prefix('u/{group}')
        ->middleware('group')
        ->name('u.')
        ->scopeBindings()
        ->group(function () {
            Route::get('/', [OverviewController::class, 'show'])->name('dashboard');

            // "What have I put in, and what is it worth?"
            //
            // Paying in is a short, one-question-per-page wizard rather than the
            // dense multi-field form /g uses — see Member\ContributeWizardController.
            // It ends at the same, unchanged TransactionController::store an
            // administrator's own form posts to, so nothing about validation or
            // what a contribution *is* differs between the two surfaces.
            Route::get('money', [MoneyController::class, 'show'])->name('money');
            Route::get('money/contribute', [ContributeWizardController::class, 'treasury'])->name('money.contribute');
            Route::post('money/contribute', [ContributeWizardController::class, 'treasuryStore']);
            Route::get('money/contribute/amount', [ContributeWizardController::class, 'amount'])->name('money.contribute.amount');
            Route::post('money/contribute/amount', [ContributeWizardController::class, 'amountStore']);
            Route::get('money/contribute/review', [ContributeWizardController::class, 'review'])->name('money.contribute.review');
            Route::post('money/contribute/submit', [TransactionController::class, 'store'])->name('money.store');

            // "Can I borrow, what do I owe, and how do I pay it back?"
            //
            // Requesting a loan and repaying one are the same kind of short wizard,
            // both ending at the existing LoanController actions, unchanged.
            Route::get('borrowing', [BorrowingController::class, 'show'])->name('borrowing');

            Route::get('borrowing/request', [LoanRequestWizardController::class, 'amount'])->name('borrowing.request');
            Route::post('borrowing/request', [LoanRequestWizardController::class, 'amountStore']);
            Route::get('borrowing/request/term', [LoanRequestWizardController::class, 'term'])->name('borrowing.request.term');
            Route::post('borrowing/request/term', [LoanRequestWizardController::class, 'termStore']);
            Route::get('borrowing/request/review', [LoanRequestWizardController::class, 'review'])->name('borrowing.request.review');
            Route::post('borrowing/request/submit', [LoanController::class, 'store'])->name('borrowing.store');

            Route::get('borrowing/{loan}', [LoanController::class, 'show'])->name('borrowing.loan');

            // Repaying has no administrative counterpart at this URL — an admin
            // records a repayment on someone's behalf from the shared loan page
            // instead (loans/show.blade.php) — this wizard is member-only.
            Route::get('borrowing/{loan}/repay', [LoanRepayWizardController::class, 'treasury'])->name('borrowing.repay.start');
            Route::post('borrowing/{loan}/repay/treasury', [LoanRepayWizardController::class, 'treasuryStore']);
            Route::get('borrowing/{loan}/repay/amount', [LoanRepayWizardController::class, 'amount'])->name('borrowing.repay.amount');
            Route::post('borrowing/{loan}/repay/amount', [LoanRepayWizardController::class, 'amountStore']);
            Route::get('borrowing/{loan}/repay/review', [LoanRepayWizardController::class, 'review'])->name('borrowing.repay.review');
            Route::post('borrowing/{loan}/repay', [LoanController::class, 'repay'])->name('borrowing.repay');
            Route::post('borrowing/{loan}/cancel', [LoanController::class, 'cancel'])->name('borrowing.cancel');

            // "What has happened in this fund?"
            Route::get('activity', [TransactionController::class, 'index'])->name('activity');
            Route::get('activity/{transaction}', [TransactionController::class, 'show'])->name('activity.show');
            Route::post('activity/{transaction}/receipts', [TransactionController::class, 'storeReceipt'])
                ->name('activity.receipts.store');
            Route::get('receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');

            Route::get('wallets', [WalletController::class, 'index'])->name('wallets.index');
            Route::post('wallets', [WalletController::class, 'store'])->name('wallets.store');
            Route::patch('wallets/{wallet}', [WalletController::class, 'update'])->name('wallets.update');

            /*
            | "Is this fund healthy, who else is in it, and what are the rules?"
            | The transparency corner — read-only by construction, and phrased in
            | fund terms. The double-entry books behind these figures are on /g.
            */
            Route::get('fund', [MemberFundController::class, 'show'])->name('fund');
            Route::get('fund/rules', [MemberFundController::class, 'rules'])->name('fund.rules');
            Route::get('fund/members', [MemberController::class, 'index'])->name('fund.members');
            Route::get('fund/members/{membership}', [MemberController::class, 'show'])->name('fund.member');
        });

    /*
    |---------------------------------------------------------------------------
    | /g/{group} — the fund administrator surface
    |---------------------------------------------------------------------------
    | The whole prefix is behind 'group' + 'group.admin', so membership alone is
    | not enough: reaching any of it needs GroupMembership::ROLE_ADMIN in this
    | fund. 'group' still verifies that every bound model belongs to the fund;
    | scoped bindings resolve children through the group relation as a second
    | layer.
    */
    Route::prefix('g/{group}')
        ->middleware(['group', 'group.admin'])
        ->name('g.')
        ->scopeBindings()
        ->group(function () {
            Route::get('/', [HubController::class, 'show'])->name('dashboard');

            Route::get('members', [MemberController::class, 'index'])->name('members.index');
            Route::get('members/requests', [MemberController::class, 'requests'])->name('members.requests');
            Route::get('members/{membership}', [MemberController::class, 'show'])->name('members.show');
            Route::post('members/{membership}/approve', [MemberController::class, 'approve'])->name('members.approve');
            Route::post('members/{membership}/reject', [MemberController::class, 'reject'])->name('members.reject');
            Route::post('members/{membership}/suspend', [MemberController::class, 'suspend'])->name('members.suspend');
            Route::post('members/{membership}/reinstate', [MemberController::class, 'reinstate'])->name('members.reinstate');
            Route::post('members/{membership}/role', [MemberController::class, 'changeRole'])->name('members.role');

            Route::get('treasuries', [TreasuryController::class, 'index'])->name('treasuries.index');
            Route::post('treasuries', [TreasuryController::class, 'store'])->name('treasuries.store');
            Route::patch('treasuries/{treasury}', [TreasuryController::class, 'update'])->name('treasuries.update');
            Route::post('treasuries/{treasury}/reconcile', [TreasuryController::class, 'reconcile'])->name('treasuries.reconcile');

            Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
            Route::get('transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
            Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
            Route::get('transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
            Route::post('transactions/{transaction}/receipts', [TransactionController::class, 'storeReceipt'])->name('transactions.receipts.store');
            Route::post('transactions/{transaction}/verify', [TransactionController::class, 'verify'])->name('transactions.verify');
            Route::post('transactions/{transaction}/reject', [TransactionController::class, 'reject'])->name('transactions.reject');
            Route::post('transactions/{transaction}/chain-check', [TransactionController::class, 'chainCheck'])->name('transactions.chain-check');

            Route::get('receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');

            Route::get('loans', [LoanController::class, 'index'])->name('loans.index');
            Route::get('loans/{loan}', [LoanController::class, 'show'])->name('loans.show');
            Route::post('loans/{loan}/approve', [LoanController::class, 'approve'])->name('loans.approve');
            Route::post('loans/{loan}/reject', [LoanController::class, 'reject'])->name('loans.reject');
            Route::post('loans/{loan}/disburse', [LoanController::class, 'disburse'])->name('loans.disburse');
            // An administrator may record a repayment on a member's behalf.
            Route::post('loans/{loan}/repay', [LoanController::class, 'repay'])->name('loans.repay');

            Route::get('ledger', [LedgerController::class, 'index'])->name('ledger.index');
            Route::get('ledger/adjustments/create', [LedgerController::class, 'createAdjustment'])->name('ledger.adjustments.create');
            Route::post('ledger/adjustments', [LedgerController::class, 'storeAdjustment'])->name('ledger.adjustments.store');
            Route::get('ledger/{journalEntry}', [LedgerController::class, 'show'])->name('ledger.show');
            Route::post('ledger/{journalEntry}/reverse', [LedgerController::class, 'reverse'])->name('ledger.reverse');

            Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
            Route::get('accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');

            Route::get('cost-centers', [CostCenterController::class, 'index'])->name('cost-centers.index');
            Route::get('cost-centers/{costCenter}', [CostCenterController::class, 'show'])->name('cost-centers.show');
            Route::post('cost-centers', [CostCenterController::class, 'store'])->name('cost-centers.store');

            Route::get('periods', [AccountingPeriodController::class, 'index'])->name('periods.index');
            Route::post('periods/{accountingPeriod}/close', [AccountingPeriodController::class, 'close'])->name('periods.close');
            Route::post('periods/{accountingPeriod}/reopen', [AccountingPeriodController::class, 'reopen'])->name('periods.reopen');

            Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');

            Route::get('audit', [AuditController::class, 'index'])->name('audit.index');

            // The fund's own identity — name and description. Plain Group
            // columns, not a policy field, so there is no version history.
            Route::get('settings', [GroupController::class, 'editSettings'])->name('settings.edit');
            Route::put('settings', [GroupController::class, 'updateSettings'])->name('settings.update');

            // The fund's optional, advisory financial framework — a Group
            // concept, not a GroupPolicy version, but grouped here for
            // discoverability.
            Route::get('framework', [GroupController::class, 'editFramework'])->name('framework.edit');
            Route::put('framework', [GroupController::class, 'updateFramework'])->name('framework.update');

            /*
            | Versioned policies. Versions are addressed by their number, scoped
            | to the group, so /g/a/policies/3 and /g/b/policies/3 are different
            | documents and neither can be reached from the other. Members read
            | the rules that govern them at /u/{group}/fund/rules instead —
            | drafts and version history are an administrative concern.
            */
            Route::get('policies', [PolicyController::class, 'index'])->name('policies.index');
            Route::post('policies', [PolicyController::class, 'store'])->name('policies.store');
            Route::get('policies/{policy:version}', [PolicyController::class, 'show'])->name('policies.show');
            Route::get('policies/{policy:version}/compare/{other}', [PolicyController::class, 'compare'])->name('policies.compare');
            Route::get('policies/{policy:version}/edit', [PolicyController::class, 'edit'])->name('policies.edit');
            Route::put('policies/{policy:version}', [PolicyController::class, 'update'])->name('policies.update');
            Route::delete('policies/{policy:version}', [PolicyController::class, 'destroy'])->name('policies.destroy');
            Route::get('policies/{policy:version}/publish', [PolicyController::class, 'confirmPublish'])->name('policies.publish.confirm');
            Route::post('policies/{policy:version}/publish', [PolicyController::class, 'publish'])->name('policies.publish');
        });

    /*
    | Bookmarks from before the URL space was partitioned. /admin moved wholesale
    | to /s; the member pages that used to live under /g moved to /u. Paths that
    | still exist under /g are left alone — a member who follows one now meets
    | the 403 that prefix has always given non-administrators.
    */
    Route::permanentRedirect('/admin', '/s');
    Route::permanentRedirect('/admin/{path}', '/s/{path}')->where('path', '.*');
    Route::permanentRedirect('/g/{group}/wallets', '/u/{group}/wallets');
});
