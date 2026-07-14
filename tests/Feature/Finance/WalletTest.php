<?php

declare(strict_types=1);

use App\Actions\Companies\CreateCompany;
use App\Actions\Transactions\CreateTransaction;
use App\Actions\Wallets\CreateWallet;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\User;

function financeCompany(User $user): Company
{
    return app(CreateCompany::class)->handle($user, 'Acme Studio');
}

test('a new company gets default BD wallets and categories', function () {
    $user = User::factory()->create();
    $company = financeCompany($user);

    expect($company->wallets()->pluck('name')->sort()->values()->all())->toBe(['Bank', 'Card', 'Cash', 'Mobile Wallet'])
        ->and($company->categories()->where('kind', 'income')->count())->toBeGreaterThanOrEqual(2)
        ->and($company->categories()->where('kind', 'expense')->count())->toBeGreaterThanOrEqual(3);
});

test('creating a wallet with a positive opening balance caches the balance', function () {
    $user = User::factory()->create();
    $company = financeCompany($user);

    $wallet = app(CreateWallet::class)->handle($company, 'Payroll Account', WalletType::Bank, openingBalance: 500_000, creator: $user);

    expect($wallet->opening_balance)->toBe(500_000)
        ->and($wallet->cached_balance)->toBe(500_000)
        ->and($wallet->derivedBalance())->toBe(500_000);
});

test('creating a wallet with a negative opening balance works for credit accounts', function () {
    $user = User::factory()->create();
    $company = financeCompany($user);

    $wallet = app(CreateWallet::class)->handle($company, 'Credit Card', WalletType::Card, openingBalance: -120_000);

    expect($wallet->cached_balance)->toBe(-120_000)
        ->and($wallet->derivedBalance())->toBe(-120_000);
});

test('a wallet can be created through the endpoint with a decimal opening balance', function () {
    $user = User::factory()->create();
    $company = financeCompany($user);

    $response = $this->actingAs($user)->post(route('wallets.store', ['current_company' => $company->slug]), [
        'name' => 'Rocket',
        'type' => WalletType::MobileBanking->value,
        'opening_balance' => '1500.50',
    ]);

    $response->assertRedirect();

    $wallet = $company->wallets()->where('name', 'Rocket')->firstOrFail();

    expect($wallet->cached_balance)->toBe(150_050);
});

test('a wallet from another company returns 404 via scoped bindings', function () {
    $owner = User::factory()->create();
    $company = financeCompany($owner);

    $otherOwner = User::factory()->create();
    $otherCompany = app(CreateCompany::class)->handle($otherOwner, 'Other Business');
    $foreignWallet = $otherCompany->wallets()->firstOrFail();

    $this->actingAs($owner)
        ->get(route('wallets.show', ['current_company' => $company->slug, 'wallet' => $foreignWallet->id]))
        ->assertNotFound();
});

test('archiving a wallet toggles archived state', function () {
    $user = User::factory()->create();
    $company = financeCompany($user);
    $wallet = $company->wallets()->firstOrFail();

    $this->actingAs($user)
        ->patch(route('wallets.archive', ['current_company' => $company->slug, 'wallet' => $wallet->id]))
        ->assertRedirect();

    expect($wallet->refresh()->isArchived())->toBeTrue();

    $this->actingAs($user)
        ->patch(route('wallets.archive', ['current_company' => $company->slug, 'wallet' => $wallet->id]))
        ->assertRedirect();

    expect($wallet->refresh()->isArchived())->toBeFalse();
});

test('editing the opening balance shifts the cached balance by the difference', function () {
    $user = User::factory()->create();
    $company = financeCompany($user);

    $wallet = app(CreateWallet::class)->handle($company, 'Payroll Account', WalletType::Bank, openingBalance: 500_000, creator: $user);

    app(CreateTransaction::class)->handle(
        $company,
        TransactionType::Income,
        $wallet,
        100_000,
        now(),
        $company->categories()->where('kind', 'income')->whereNull('parent_id')->firstOrFail(),
        creator: $user,
    );

    $this->actingAs($user)->put(route('wallets.update', ['current_company' => $company->slug, 'wallet' => $wallet->id]), [
        'name' => 'Payroll Account',
        'type' => 'bank',
        'opening_balance' => '700',
    ])->assertRedirect();

    $wallet->refresh();

    expect($wallet->opening_balance)->toBe(70_000)
        ->and($wallet->cached_balance)->toBe(170_000)
        ->and($wallet->derivedBalance())->toBe(170_000);
});
