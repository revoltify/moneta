<?php

declare(strict_types=1);

use App\Actions\Categories\CreateCategory;
use App\Actions\Companies\CreateCompany;
use App\Actions\Transactions\CreateTransaction;
use App\Actions\Transactions\CreateTransfer;
use App\Actions\Transactions\VoidTransaction;
use App\Enums\CategoryKind;
use App\Enums\TransactionType;
use App\Models\User;
use App\Services\Reports\BalanceSheetReport;
use App\Services\Reports\CashFlowReport;
use App\Services\Reports\IncomeStatementReport;
use Inertia\Testing\AssertableInertia;

function reportSetup(): array
{
    $user = User::factory()->create();
    $company = resolve(CreateCompany::class)->handle($user, 'Acme Studio');
    $bank = $company->wallets()->where('name', 'Bank')->firstOrFail();
    $cash = $company->wallets()->where('name', 'Cash')->firstOrFail();
    $commission = $company->categories()->where('name', 'Sales')->firstOrFail();
    $hosting = $company->categories()->where('name', 'Software & Hosting')->firstOrFail();

    return [$user, $company, $bank, $cash, $commission, $hosting];
}

test('the income statement respects the period and rolls children into parents', function (): void {
    [$user, $company, $bank, , $commission, $hosting] = reportSetup();

    $vpsChild = resolve(CreateCategory::class)->handle($company, 'VPS', CategoryKind::Expense, $hosting->fresh());

    resolve(CreateTransaction::class)->handle($company, TransactionType::Income, $bank, 500_000, now(), $commission);
    resolve(CreateTransaction::class)->handle($company, TransactionType::Expense, $bank, 100_000, now(), $hosting);
    resolve(CreateTransaction::class)->handle($company, TransactionType::Expense, $bank, 50_000, now(), $vpsChild);

    resolve(CreateTransaction::class)->handle($company, TransactionType::Income, $bank, 999_999, now()->subMonths(2), $commission);

    $report = resolve(IncomeStatementReport::class)->generate($company, now()->startOfMonth(), now()->endOfMonth());

    expect($report['totalIncome'])->toBe(500_000)
        ->and($report['totalExpense'])->toBe(150_000)
        ->and($report['netProfit'])->toBe(350_000);

    $hostingRow = collect($report['expense'])->firstWhere('id', $hosting->id);

    expect($hostingRow['amount'])->toBe(150_000)
        ->and($hostingRow['children'][0]['name'])->toBe('VPS')
        ->and($hostingRow['children'][0]['amount'])->toBe(50_000);
});

test('a voided transaction never appears in reports', function (): void {
    [$user, $company, $bank, , $commission] = reportSetup();

    $transaction = resolve(CreateTransaction::class)->handle($company, TransactionType::Income, $bank, 500_000, now(), $commission);
    resolve(VoidTransaction::class)->handle($transaction);

    $report = resolve(IncomeStatementReport::class)->generate($company, now()->startOfMonth(), now()->endOfMonth());

    expect($report['totalIncome'])->toBe(0);
});

test('the daily summary reports the last 30 days per day', function (): void {
    [$user, $company, $bank, $cash, $commission, $hosting] = reportSetup();

    resolve(CreateTransaction::class)->handle($company, TransactionType::Income, $bank, 500_000, now(), $commission);
    resolve(CreateTransaction::class)->handle($company, TransactionType::Expense, $cash, 120_000, now(), $hosting);
    resolve(CreateTransaction::class)->handle($company, TransactionType::Income, $bank, 200_000, now()->subDay(), $commission);
    resolve(CreateTransaction::class)->handle($company, TransactionType::Income, $bank, 900_000, now()->subDays(45), $commission);

    $this->actingAs($user)
        ->get(route('reports.daily-summary', ['current_company' => $company->slug]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('reports/daily-summary')
            ->has('days', 30)
            ->where('days.29.income', 500_000)
            ->where('days.29.expense', 120_000)
            ->where('days.29.profit', 380_000)
            ->where('days.28.income', 200_000)
            ->where('totals.income', 700_000)
            ->where('totals.expense', 120_000)
            ->where('totals.profit', 580_000));
});

test('the balance sheet satisfies assets equal equity', function (): void {
    [$user, $company, $bank, $cash, $commission, $hosting] = reportSetup();

    resolve(CreateTransaction::class)->handle($company, TransactionType::CapitalInvestment, $bank, 2_000_000, now()->subMonth());
    resolve(CreateTransaction::class)->handle($company, TransactionType::Income, $bank, 800_000, now(), $commission);
    resolve(CreateTransaction::class)->handle($company, TransactionType::Expense, $cash, 150_000, now(), $hosting);
    resolve(CreateTransfer::class)->handle($company, $bank, $cash, 300_000, now());
    resolve(CreateTransaction::class)->handle($company, TransactionType::CapitalWithdrawal, $bank, 250_000, now());

    $report = resolve(BalanceSheetReport::class)->generate($company, now());

    expect($report['totalAssets'])->toBe($report['totalEquity'])
        ->and($report['totalAssets'])->toBe(2_000_000 + 800_000 - 150_000 - 250_000)
        ->and($report['retainedEarnings'])->toBe(800_000 - 150_000);

    $historical = resolve(BalanceSheetReport::class)->generate($company, now()->subMonth()->endOfMonth());

    expect($historical['totalAssets'])->toBe(2_000_000)
        ->and($historical['totalAssets'])->toBe($historical['totalEquity']);
});

test('the cash flow net change equals the wallet balance delta and classifies financing', function (): void {
    [$user, $company, $bank, $cash, $commission, $hosting] = reportSetup();

    resolve(CreateTransaction::class)->handle($company, TransactionType::CapitalInvestment, $bank, 1_000_000, now()->subMonths(2));

    $balancesBefore = $company->wallets()->sum('cached_balance');

    resolve(CreateTransaction::class)->handle($company, TransactionType::Income, $bank, 600_000, now(), $commission);
    resolve(CreateTransaction::class)->handle($company, TransactionType::Expense, $cash, 100_000, now(), $hosting);
    resolve(CreateTransfer::class)->handle($company, $bank, $cash, 200_000, now());
    resolve(CreateTransaction::class)->handle($company, TransactionType::CapitalWithdrawal, $bank, 150_000, now());

    $balancesAfter = $company->wallets()->sum('cached_balance');

    $report = resolve(CashFlowReport::class)->generate($company, now()->startOfMonth(), now()->endOfMonth());

    expect($report['netChange'])->toBe((int) ($balancesAfter - $balancesBefore))
        ->and($report['netOperating'])->toBe(600_000 - 100_000)
        ->and($report['netFinancing'])->toBe(-150_000)
        ->and($report['openingBalance'])->toBe(1_000_000)
        ->and($report['closingBalance'])->toBe((int) $balancesAfter);
});

test('the report pages render', function (): void {
    [$user, $company] = reportSetup();

    $this->actingAs($user)->get(route('reports.index', ['current_company' => $company->slug]))->assertOk();
    $this->actingAs($user)->get(route('reports.income-statement', ['current_company' => $company->slug]))->assertOk();
    $this->actingAs($user)->get(route('reports.balance-sheet', ['current_company' => $company->slug]))->assertOk();
    $this->actingAs($user)->get(route('reports.cash-flow', ['current_company' => $company->slug]))->assertOk();
    $this->actingAs($user)->get(route('reports.category-breakdown', ['current_company' => $company->slug]))->assertOk();
    $this->actingAs($user)->get(route('reports.monthly-summary', ['current_company' => $company->slug]))->assertOk();
    $this->actingAs($user)->get(route('dashboard', ['current_company' => $company->slug]))->assertOk();
});

test('the category breakdown reports share per category', function (): void {
    [$user, $company, $bank, , $commission, $hosting] = reportSetup();

    resolve(CreateTransaction::class)->handle($company, TransactionType::Expense, $bank, 75_000, now(), $hosting);
    $marketing = $company->categories()->where('name', 'Marketing')->firstOrFail();
    resolve(CreateTransaction::class)->handle($company, TransactionType::Expense, $bank, 25_000, now(), $marketing);

    $this->actingAs($user)
        ->get(route('reports.category-breakdown', ['current_company' => $company->slug, 'kind' => 'expense']))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('reports/category-breakdown')
            ->where('total', 100_000)
            ->count('rows', 2));
});

test('the monthly summary covers twelve months and sums correctly', function (): void {
    [$user, $company, $bank, , $commission, $hosting] = reportSetup();

    resolve(CreateTransaction::class)->handle($company, TransactionType::Income, $bank, 500_000, now(), $commission);
    resolve(CreateTransaction::class)->handle($company, TransactionType::Expense, $bank, 200_000, now()->subMonthsNoOverflow(2), $hosting);

    $this->actingAs($user)
        ->get(route('reports.monthly-summary', ['current_company' => $company->slug]))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('reports/monthly-summary')
            ->count('months', 12)
            ->where('totals.income', 500_000)
            ->where('totals.expense', 200_000)
            ->where('totals.profit', 300_000));
});

test('the dashboard stats respect the period filter', function (): void {
    [$user, $company, $bank, , $commission] = reportSetup();

    resolve(CreateTransaction::class)->handle($company, TransactionType::Income, $bank, 500_000, now(), $commission);
    resolve(CreateTransaction::class)->handle($company, TransactionType::Income, $bank, 999_999, now()->subMonths(2), $commission);

    $this->actingAs($user)
        ->get(route('dashboard', ['current_company' => $company->slug]))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('periodIncome', 500_000));

    $this->actingAs($user)
        ->get(route('dashboard', [
            'current_company' => $company->slug,
            'from' => now()->subMonths(3)->toDateString(),
            'to' => now()->toDateString(),
        ]))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('periodIncome', 1_499_999));
});
