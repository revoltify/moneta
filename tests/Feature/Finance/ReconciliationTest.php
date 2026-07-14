<?php

use App\Actions\Companies\CreateCompany;
use App\Actions\Transactions\CreateTransaction;
use App\Actions\Wallets\ReconcileWallet;
use App\Enums\TransactionType;
use App\Models\User;

function reconcileSetup(): array
{
    $user = User::factory()->create();
    $company = app(CreateCompany::class)->handle($user, 'Acme Studio');
    $bank = $company->wallets()->where('name', 'Bank')->firstOrFail();

    app(CreateTransaction::class)->handle($company, TransactionType::CapitalInvestment, $bank, 1_000_000, now());

    return [$user, $company, $bank];
}

test('a shortage posts an expense adjustment and the balance matches reality', function () {
    [$user, $company, $bank] = reconcileSetup();

    $transaction = app(ReconcileWallet::class)->handle($bank, 950_000, $user);

    expect($transaction->type)->toBe(TransactionType::Expense)
        ->and($transaction->amount)->toBe(50_000)
        ->and($transaction->category->name)->toBe(ReconcileWallet::ADJUSTMENT_CATEGORY)
        ->and($bank->refresh()->cached_balance)->toBe(950_000)
        ->and($bank->derivedBalance())->toBe(950_000);

    $this->artisan('moneta:verify-balances')->assertSuccessful();
});

test('an overage posts an income adjustment', function () {
    [$user, $company, $bank] = reconcileSetup();

    $transaction = app(ReconcileWallet::class)->handle($bank, 1_020_000, $user);

    expect($transaction->type)->toBe(TransactionType::Income)
        ->and($transaction->amount)->toBe(20_000)
        ->and($bank->refresh()->cached_balance)->toBe(1_020_000);
});

test('a matching balance posts nothing', function () {
    [$user, $company, $bank] = reconcileSetup();

    expect(app(ReconcileWallet::class)->handle($bank, 1_000_000, $user))->toBeNull()
        ->and($company->transactions()->count())->toBe(1);
});

test('reconciliation works through the endpoint with a decimal balance', function () {
    [$user, $company, $bank] = reconcileSetup();

    $this->actingAs($user)
        ->post(route('wallets.reconcile', ['current_company' => $company->slug, 'wallet' => $bank->id]), [
            'actual_balance' => '9500.50',
        ])
        ->assertRedirect();

    expect($bank->refresh()->cached_balance)->toBe(950_050);
});

test('repeated reconciliation reuses the adjustment category', function () {
    [$user, $company, $bank] = reconcileSetup();

    app(ReconcileWallet::class)->handle($bank, 950_000, $user);
    app(ReconcileWallet::class)->handle($bank, 900_000, $user);

    expect($company->categories()->where('name', ReconcileWallet::ADJUSTMENT_CATEGORY)->count())->toBe(1);
});
