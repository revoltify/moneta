<?php

use App\Models\Budget;
use App\Models\Company;
use App\Models\RecurringTransaction;
use App\Models\User;
use Database\Seeders\DemoSeeder;

test('the demo seeder produces a fully reconciled set of books', function () {
    $this->seed(DemoSeeder::class);

    $company = Company::query()->where('name', 'Acme Studio')->firstOrFail();

    expect($company->wallets()->count())->toBe(4)
        ->and($company->transactions()->count())->toBeGreaterThan(20)
        ->and(Budget::query()->forCompany($company)->count())->toBe(2)
        ->and(RecurringTransaction::query()->forCompany($company)->count())->toBe(3);

    $this->artisan('moneta:verify-balances')->assertSuccessful();
});

test('every finance page renders for the demo owner', function () {
    $this->seed(DemoSeeder::class);

    $owner = User::where('email', 'demo@example.com')->firstOrFail();
    $company = Company::query()->where('name', 'Acme Studio')->firstOrFail();
    $slug = ['current_company' => $company->slug];
    $wallet = $company->wallets()->firstOrFail();

    foreach ([
        route('dashboard', $slug),
        route('wallets.index', $slug),
        route('wallets.show', [...$slug, 'wallet' => $wallet->id]),
        route('categories.index', $slug),
        route('transactions.index', $slug),
        route('budgets.index', $slug),
        route('recurring.index', $slug),
        route('reports.index', $slug),
        route('reports.income-statement', $slug),
        route('reports.category-breakdown', $slug),
        route('reports.monthly-summary', $slug),
        route('reports.balance-sheet', $slug),
        route('reports.cash-flow', $slug),
        route('audit.index', $slug),
    ] as $url) {
        $this->actingAs($owner)->get($url)->assertOk();
    }
});
