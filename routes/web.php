<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountingPeriodController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TreasuryController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

/*
| Server-rendered HTTP only: GET renders Blade, POST validates, authorises,
| runs one database transaction and redirects. No AJAX, no API surface.
|
| The canonical group prefix is /g/{group}; there is no /groups/.
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('/g/create', [GroupController::class, 'create'])->name('groups.create');
    Route::post('/g', [GroupController::class, 'store'])->name('groups.store');

    // Joining happens before membership exists, so these two sit outside the
    // group middleware and do their own checks.
    Route::get('/g/{group}/join', [GroupController::class, 'showJoin'])->name('groups.join');
    Route::post('/g/{group}/join', [GroupController::class, 'join']);

    Route::get('/exchange-rates', [ExchangeRateController::class, 'index'])->name('exchange-rates.index');
    Route::post('/exchange-rates', [ExchangeRateController::class, 'store'])->name('exchange-rates.store');

    /*
    | Everything below is inside one fund. The 'group' middleware verifies
    | membership and that every bound model belongs to that fund; scoped
    | bindings resolve children through the group relation as a second layer.
    */
    Route::prefix('g/{group}')
        ->middleware('group')
        ->name('g.')
        ->scopeBindings()
        ->group(function () {
            Route::get('/', [DashboardController::class, 'show'])->name('dashboard');

            Route::get('members', [MemberController::class, 'index'])->name('members.index');
            Route::get('members/requests', [MemberController::class, 'requests'])
                ->middleware('group.admin')->name('members.requests');
            Route::get('members/{membership}', [MemberController::class, 'show'])->name('members.show');

            Route::middleware('group.admin')->group(function () {
                Route::post('members/{membership}/approve', [MemberController::class, 'approve'])->name('members.approve');
                Route::post('members/{membership}/reject', [MemberController::class, 'reject'])->name('members.reject');
                Route::post('members/{membership}/suspend', [MemberController::class, 'suspend'])->name('members.suspend');
                Route::post('members/{membership}/reinstate', [MemberController::class, 'reinstate'])->name('members.reinstate');
                Route::post('members/{membership}/role', [MemberController::class, 'changeRole'])->name('members.role');
            });

            Route::get('wallets', [WalletController::class, 'index'])->name('wallets.index');
            Route::post('wallets', [WalletController::class, 'store'])->name('wallets.store');
            Route::patch('wallets/{wallet}', [WalletController::class, 'update'])->name('wallets.update');

            Route::get('treasuries', [TreasuryController::class, 'index'])->name('treasuries.index');
            Route::middleware('group.admin')->group(function () {
                Route::post('treasuries', [TreasuryController::class, 'store'])->name('treasuries.store');
                Route::patch('treasuries/{treasury}', [TreasuryController::class, 'update'])->name('treasuries.update');
                Route::post('treasuries/{treasury}/reconcile', [TreasuryController::class, 'reconcile'])->name('treasuries.reconcile');
            });

            Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
            Route::get('transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
            Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
            Route::get('transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
            Route::post('transactions/{transaction}/receipts', [TransactionController::class, 'storeReceipt'])->name('transactions.receipts.store');
            Route::middleware('group.admin')->group(function () {
                Route::post('transactions/{transaction}/verify', [TransactionController::class, 'verify'])->name('transactions.verify');
                Route::post('transactions/{transaction}/reject', [TransactionController::class, 'reject'])->name('transactions.reject');
                Route::post('transactions/{transaction}/chain-check', [TransactionController::class, 'chainCheck'])->name('transactions.chain-check');
            });

            Route::get('receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');

            Route::get('loans', [LoanController::class, 'index'])->name('loans.index');
            Route::get('loans/create', [LoanController::class, 'create'])->name('loans.create');
            Route::post('loans', [LoanController::class, 'store'])->name('loans.store');
            Route::get('loans/{loan}', [LoanController::class, 'show'])->name('loans.show');
            Route::post('loans/{loan}/repay', [LoanController::class, 'repay'])->name('loans.repay');
            Route::post('loans/{loan}/cancel', [LoanController::class, 'cancel'])->name('loans.cancel');
            Route::middleware('group.admin')->group(function () {
                Route::post('loans/{loan}/approve', [LoanController::class, 'approve'])->name('loans.approve');
                Route::post('loans/{loan}/reject', [LoanController::class, 'reject'])->name('loans.reject');
                Route::post('loans/{loan}/disburse', [LoanController::class, 'disburse'])->name('loans.disburse');
            });

            Route::get('ledger', [LedgerController::class, 'index'])->name('ledger.index');
            Route::get('ledger/adjustments/create', [LedgerController::class, 'createAdjustment'])
                ->middleware('group.admin')->name('ledger.adjustments.create');
            Route::post('ledger/adjustments', [LedgerController::class, 'storeAdjustment'])
                ->middleware('group.admin')->name('ledger.adjustments.store');
            Route::get('ledger/{journalEntry}', [LedgerController::class, 'show'])->name('ledger.show');
            Route::post('ledger/{journalEntry}/reverse', [LedgerController::class, 'reverse'])
                ->middleware('group.admin')->name('ledger.reverse');

            Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
            Route::get('accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');

            Route::get('cost-centers', [CostCenterController::class, 'index'])->name('cost-centers.index');
            Route::get('cost-centers/{costCenter}', [CostCenterController::class, 'show'])->name('cost-centers.show');
            Route::post('cost-centers', [CostCenterController::class, 'store'])
                ->middleware('group.admin')->name('cost-centers.store');

            Route::middleware('group.admin')->group(function () {
                Route::get('periods', [AccountingPeriodController::class, 'index'])->name('periods.index');
                Route::post('periods/{accountingPeriod}/close', [AccountingPeriodController::class, 'close'])->name('periods.close');
                Route::post('periods/{accountingPeriod}/reopen', [AccountingPeriodController::class, 'reopen'])->name('periods.reopen');
            });

            Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');

            Route::get('audit', [AuditController::class, 'index'])
                ->middleware('group.admin')->name('audit.index');

            /*
            | Versioned policies. Versions are addressed by their number, scoped
            | to the group, so /g/a/policies/3 and /g/b/policies/3 are different
            | documents and neither can be reached from the other.
            */
            Route::get('policies', [PolicyController::class, 'index'])->name('policies.index');
            Route::post('policies', [PolicyController::class, 'store'])
                ->middleware('group.admin')->name('policies.store');
            Route::get('policies/{policy:version}', [PolicyController::class, 'show'])->name('policies.show');
            Route::get('policies/{policy:version}/compare/{other}', [PolicyController::class, 'compare'])->name('policies.compare');
            Route::middleware('group.admin')->group(function () {
                Route::get('policies/{policy:version}/edit', [PolicyController::class, 'edit'])->name('policies.edit');
                Route::put('policies/{policy:version}', [PolicyController::class, 'update'])->name('policies.update');
                Route::delete('policies/{policy:version}', [PolicyController::class, 'destroy'])->name('policies.destroy');
                Route::get('policies/{policy:version}/publish', [PolicyController::class, 'confirmPublish'])->name('policies.publish.confirm');
                Route::post('policies/{policy:version}/publish', [PolicyController::class, 'publish'])->name('policies.publish');
            });
        });
});
