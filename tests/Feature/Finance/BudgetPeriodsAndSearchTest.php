<?php

declare(strict_types=1);

use App\Actions\Budgets\EvaluateBudgetAlert;
use App\Actions\Companies\CreateCompany;
use App\Actions\Transactions\CreateTransaction;
use App\Enums\TransactionType;
use App\Models\Budget;
use App\Models\User;

function periodSetup(): array
{
    $user = User::factory()->create();
    $company = app(CreateCompany::class)->handle($user, 'Acme Studio');
    $bank = $company->wallets()->where('name', 'Bank')->firstOrFail();
    $marketing = $company->categories()->where('name', 'Marketing')->firstOrFail();

    return [$user, $company, $bank, $marketing];
}

test('a quarterly budget accumulates spend across the whole quarter', function () {
    [$user, $company, $bank, $marketing] = periodSetup();

    Budget::create([
        'company_id' => $company->id,
        'category_id' => $marketing->id,
        'period' => 'quarterly',
        'amount' => 300_000,
        'alert_threshold' => 80,
    ]);

    $quarterStart = now()->firstOfQuarter();

    app(CreateTransaction::class)->handle($company, TransactionType::Expense, $bank, 150_000, $quarterStart, $marketing);
    app(CreateTransaction::class)->handle($company, TransactionType::Expense, $bank, 100_000, now(), $marketing);

    $evaluator = app(EvaluateBudgetAlert::class);

    expect($evaluator->periodSpend($company, $marketing, now(), 'quarterly'))->toBe(250_000);

    $alert = $evaluator->handle($company, $marketing, now());

    expect($alert)->not->toBeNull()
        ->and($alert['level'])->toBe('warning')
        ->and($alert['message'])->toContain('quarterly');
})->skip(fn () => now()->diffInDays(now()->firstOfQuarter(), true) < 1, 'First day of quarter: two windows collapse');

test('a quarterly and monthly budget can coexist for the same category', function () {
    [$user, $company, , $marketing] = periodSetup();

    $this->actingAs($user)->post(route('budgets.store', ['current_company' => $company->slug]), [
        'category_id' => $marketing->id,
        'period' => 'monthly',
        'amount' => '1000',
        'alert_threshold' => 80,
    ])->assertRedirect();

    $this->actingAs($user)->post(route('budgets.store', ['current_company' => $company->slug]), [
        'category_id' => $marketing->id,
        'period' => 'quarterly',
        'amount' => '3000',
        'alert_threshold' => 80,
    ])->assertRedirect();

    expect(Budget::query()->forCompany($company)->count())->toBe(2);
});

test('global search finds transactions, wallets and categories', function () {
    [$user, $company, $bank] = periodSetup();
    $commission = $company->categories()->where('name', 'Sales')->firstOrFail();

    app(CreateTransaction::class)->handle($company, TransactionType::Income, $bank, 100_000, now(), $commission, 'ShopHub settlement');

    $this->actingAs($user)
        ->getJson(route('search.index', ['current_company' => $company->slug, 'q' => 'ShopHub']))
        ->assertOk()
        ->assertJsonCount(1, 'transactions')
        ->assertJsonCount(0, 'wallets');

    $this->actingAs($user)
        ->getJson(route('search.index', ['current_company' => $company->slug, 'q' => 'Mobile Wallet']))
        ->assertOk()
        ->assertJsonCount(1, 'wallets');

    $this->actingAs($user)
        ->getJson(route('search.index', ['current_company' => $company->slug, 'q' => 'Sales']))
        ->assertOk()
        ->assertJsonCount(1, 'categories');
});

test('search with an empty or short query returns nothing', function () {
    [$user, $company] = periodSetup();

    $this->actingAs($user)
        ->getJson(route('search.index', ['current_company' => $company->slug]))
        ->assertOk()
        ->assertJsonCount(0, 'transactions');

    $this->actingAs($user)
        ->getJson(route('search.index', ['current_company' => $company->slug, 'q' => 'a']))
        ->assertOk()
        ->assertJsonCount(0, 'transactions');
});
