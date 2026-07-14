<?php

use App\Actions\Companies\CreateCompany;
use App\Actions\Transactions\CreateTransaction;
use App\Actions\Transactions\CreateTransfer;
use App\Actions\Wallets\CreateWallet;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\User;
use App\Services\Reports\IncomeStatementReport;

function currencySetup(): array
{
    $user = User::factory()->create();
    $company = app(CreateCompany::class)->handle($user, 'Acme Studio');
    $usdWallet = app(CreateWallet::class)->handle($company, 'Payoneer USD', WalletType::Bank, openingBalance: 100_000, currency: 'USD');

    return [$user, $company, $usdWallet];
}

test('a USD wallet tracks balances in its own currency', function () {
    [$user, $company, $usdWallet] = currencySetup();
    $commission = $company->categories()->where('name', 'Sales')->firstOrFail();

    $transaction = app(CreateTransaction::class)->handle(
        $company, TransactionType::Income, $usdWallet, 50_000, now(), $commission,
    );

    expect($transaction->currency)->toBe('USD')
        ->and($usdWallet->refresh()->cached_balance)->toBe(150_000)
        ->and($usdWallet->derivedBalance())->toBe(150_000);

    $this->artisan('moneta:verify-balances')->assertSuccessful();
});

test('cross-currency transfers are rejected', function () {
    [$user, $company, $usdWallet] = currencySetup();
    $bank = $company->wallets()->where('name', 'Bank')->firstOrFail();

    expect(fn () => app(CreateTransfer::class)->handle($company, $usdWallet, $bank, 10_000, now()))
        ->toThrow(InvalidArgumentException::class, 'different currencies');
});

test('BDT reports exclude foreign-currency activity', function () {
    [$user, $company, $usdWallet] = currencySetup();
    $bank = $company->wallets()->where('name', 'Bank')->firstOrFail();
    $commission = $company->categories()->where('name', 'Sales')->firstOrFail();

    app(CreateTransaction::class)->handle($company, TransactionType::Income, $bank, 200_000, now(), $commission);
    app(CreateTransaction::class)->handle($company, TransactionType::Income, $usdWallet, 999_999, now(), $commission);

    $report = app(IncomeStatementReport::class)->generate($company, now()->startOfMonth(), now()->endOfMonth());

    expect($report['totalIncome'])->toBe(200_000);
});
