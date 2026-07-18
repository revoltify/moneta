<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Finance\AuditLogController;
use App\Http\Controllers\Finance\BudgetController;
use App\Http\Controllers\Finance\CategoryController;
use App\Http\Controllers\Finance\RecurringTransactionController;
use App\Http\Controllers\Finance\ReportController;
use App\Http\Controllers\Finance\SearchController;
use App\Http\Controllers\Finance\TransactionController;
use App\Http\Controllers\Finance\TransferController;
use App\Http\Controllers\Finance\WalletController;
use App\Http\Middleware\SetCurrentCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn(): RedirectResponse => redirect(auth()->check() ? '/dashboard' : '/login'))->name('home');

Route::get('dashboard', function (Request $request) {
    $company = $request->user()->currentCompany ?? $request->user()->fallbackCompany();

    abort_unless($company !== null, 404);

    return to_route('dashboard', $company->slug);
})->middleware(['auth'])->name('dashboard.home');

Route::prefix('{current_company}')
    ->middleware(['auth', SetCurrentCompany::class])
    ->scopeBindings()
    ->group(function (): void {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('wallets', [WalletController::class, 'index'])->name('wallets.index');
        Route::get('wallets/{wallet}', [WalletController::class, 'show'])->name('wallets.show');
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('budgets', [BudgetController::class, 'index'])->name('budgets.index');
        Route::get('search', [SearchController::class, 'index'])->middleware('throttle:60,1')->name('search.index');
        Route::get('recurring', [RecurringTransactionController::class, 'index'])->name('recurring.index');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/category-breakdown', [ReportController::class, 'categoryBreakdown'])->name('reports.category-breakdown');
        Route::get('reports/daily-summary', [ReportController::class, 'dailySummary'])->name('reports.daily-summary');
        Route::get('reports/monthly-summary', [ReportController::class, 'monthlySummary'])->name('reports.monthly-summary');
        Route::get('reports/income-statement', [ReportController::class, 'incomeStatement'])->name('reports.income-statement');
        Route::get('reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
        Route::get('reports/cash-flow', [ReportController::class, 'cashFlow'])->name('reports.cash-flow');

        Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');

        Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
        Route::put('transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
        Route::post('transfers', [TransferController::class, 'store'])->name('transfers.store');

        Route::post('wallets', [WalletController::class, 'store'])->name('wallets.store');
        Route::put('wallets/{wallet}', [WalletController::class, 'update'])->name('wallets.update');
        Route::patch('wallets/{wallet}/archive', [WalletController::class, 'archive'])->name('wallets.archive');
        Route::post('wallets/{wallet}/reconcile', [WalletController::class, 'reconcile'])->name('wallets.reconcile');

        Route::post('budgets', [BudgetController::class, 'store'])->name('budgets.store');
        Route::delete('budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');

        Route::post('recurring', [RecurringTransactionController::class, 'store'])->name('recurring.store');
        Route::patch('recurring/{recurring_transaction}/toggle', [RecurringTransactionController::class, 'toggle'])->name('recurring.toggle');
        Route::delete('recurring/{recurring_transaction}', [RecurringTransactionController::class, 'destroy'])->name('recurring.destroy');

        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::patch('categories/{category}/archive', [CategoryController::class, 'archive'])->name('categories.archive');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    });
