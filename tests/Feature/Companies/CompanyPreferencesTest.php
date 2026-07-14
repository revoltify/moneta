<?php

declare(strict_types=1);

use App\Actions\Companies\CreateCompany;
use App\Actions\Transactions\CreateTransaction;
use App\Actions\Wallets\CreateWallet;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\User;
use App\Services\Reports\IncomeStatementReport;

test('a new company defaults to Asia/Dhaka and BDT', function () {
    $user = User::factory()->create();
    $company = app(CreateCompany::class)->handle($user, 'Acme Studio');

    expect($company->timezone)->toBe('Asia/Dhaka')
        ->and($company->currency)->toBe('BDT');
});

test('an owner can update the company preferences', function () {
    $user = User::factory()->create();
    $company = app(CreateCompany::class)->handle($user, 'Acme Studio');

    $this->actingAs($user)
        ->patch(route('companies.preferences.update', ['company' => $company->slug]), ['timezone' => 'Asia/Dubai', 'currency' => 'USD'])
        ->assertRedirect();

    expect($company->refresh()->timezone)->toBe('Asia/Dubai')
        ->and($company->currency)->toBe('USD');
});

test('an invalid timezone or currency is rejected', function () {
    $user = User::factory()->create();
    $company = app(CreateCompany::class)->handle($user, 'Acme Studio');

    $this->actingAs($user)
        ->from(route('companies.edit', ['company' => $company->slug]))
        ->patch(route('companies.preferences.update', ['company' => $company->slug]), ['timezone' => 'Mars/Olympus', 'currency' => 'XXX'])
        ->assertSessionHasErrors(['timezone', 'currency']);

    expect($company->refresh()->timezone)->toBe('Asia/Dhaka');
});

test('reports follow the company currency', function () {
    $user = User::factory()->create();
    $company = app(CreateCompany::class)->handle($user, 'Acme Studio');
    $user->switchCompany($company);

    $company->update(['currency' => 'USD']);

    $wallet = app(CreateWallet::class)->handle($company, 'US Bank', WalletType::Bank, creator: $user);
    $income = $company->categories()->where('kind', 'income')->whereNull('parent_id')->firstOrFail();

    expect($wallet->currency)->toBe('USD');

    app(CreateTransaction::class)->handle(
        $company, TransactionType::Income, $wallet, 100_000, now(), $income, creator: $user,
    );

    $report = app(IncomeStatementReport::class)->generate($company, now()->startOfMonth(), now()->endOfMonth());

    expect($report['totalIncome'])->toBe(100_000);
});
