<?php

declare(strict_types=1);

use App\Actions\Companies\CreateCompany;
use App\Actions\Transactions\CreateTransaction;
use App\Actions\Transactions\CreateTransfer;
use App\Actions\Transactions\UpdateTransaction;
use App\Actions\Transactions\VoidTransaction;
use App\Enums\TransactionType;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

function setupBooks(): array
{
    $user = User::factory()->create();
    $company = app(CreateCompany::class)->handle($user, 'Acme Studio');

    return [
        $user,
        $company,
        $company->wallets()->where('name', 'Bank')->firstOrFail(),
        $company->categories()->where('kind', 'income')->whereNull('parent_id')->firstOrFail(),
        $company->categories()->where('kind', 'expense')->whereNull('parent_id')->firstOrFail(),
    ];
}

test('an income transaction increases the wallet balance', function () {
    [$user, $company, $wallet, $income] = setupBooks();

    app(CreateTransaction::class)->handle(
        $company, TransactionType::Income, $wallet, 250_000, now(), $income, creator: $user,
    );

    expect($wallet->refresh()->cached_balance)->toBe(250_000)
        ->and($wallet->derivedBalance())->toBe(250_000);
});

test('an expense transaction decreases the wallet balance', function () {
    [$user, $company, $wallet, , $expense] = setupBooks();

    app(CreateTransaction::class)->handle(
        $company, TransactionType::Expense, $wallet, 80_000, now(), $expense,
    );

    expect($wallet->refresh()->cached_balance)->toBe(-80_000)
        ->and($wallet->derivedBalance())->toBe(-80_000);
});

test('a capital withdrawal decreases and an investment increases the wallet', function () {
    [$user, $company, $wallet] = setupBooks();

    app(CreateTransaction::class)->handle($company, TransactionType::CapitalInvestment, $wallet, 1_000_000, now());
    app(CreateTransaction::class)->handle($company, TransactionType::CapitalWithdrawal, $wallet, 100_000, now());

    expect($wallet->refresh()->cached_balance)->toBe(900_000)
        ->and($wallet->derivedBalance())->toBe(900_000);
});

test('a transfer moves balance from source to destination wallet', function () {
    [$user, $company, $bank] = setupBooks();
    $cash = $company->wallets()->where('name', 'Cash')->firstOrFail();

    app(CreateTransaction::class)->handle($company, TransactionType::CapitalInvestment, $bank, 500_000, now());
    app(CreateTransfer::class)->handle($company, $bank, $cash, 200_000, now());

    expect($bank->refresh()->cached_balance)->toBe(300_000)
        ->and($cash->refresh()->cached_balance)->toBe(200_000)
        ->and($bank->derivedBalance())->toBe(300_000)
        ->and($cash->derivedBalance())->toBe(200_000);
});

test('editing a transaction reverses the old amount and applies the new one', function () {
    [$user, $company, $wallet, $income] = setupBooks();

    $transaction = app(CreateTransaction::class)->handle(
        $company, TransactionType::Income, $wallet, 100_000, now(), $income,
    );

    app(UpdateTransaction::class)->handle(
        $transaction, $wallet, 150_000, now(), $income, description: 'Corrected amount',
    );

    expect($wallet->refresh()->cached_balance)->toBe(150_000)
        ->and($wallet->derivedBalance())->toBe(150_000);
});

test('editing a transaction onto a different wallet moves the balance', function () {
    [$user, $company, $bank, $income] = setupBooks();
    $cash = $company->wallets()->where('name', 'Cash')->firstOrFail();

    $transaction = app(CreateTransaction::class)->handle(
        $company, TransactionType::Income, $bank, 100_000, now(), $income,
    );

    app(UpdateTransaction::class)->handle($transaction, $cash, 100_000, now(), $income);

    expect($bank->refresh()->cached_balance)->toBe(0)
        ->and($cash->refresh()->cached_balance)->toBe(100_000);
});

test('voiding a transaction restores the wallet balance', function () {
    [$user, $company, $wallet, $income] = setupBooks();

    $transaction = app(CreateTransaction::class)->handle(
        $company, TransactionType::Income, $wallet, 100_000, now(), $income,
    );

    app(VoidTransaction::class)->handle($transaction);

    expect($wallet->refresh()->cached_balance)->toBe(0)
        ->and($transaction->refresh()->isPosted())->toBeFalse()
        ->and($wallet->derivedBalance())->toBe(0);
});

test('a category kind mismatch is rejected', function () {
    [$user, $company, $wallet, $income, $expense] = setupBooks();

    expect(fn () => app(CreateTransaction::class)->handle(
        $company, TransactionType::Income, $wallet, 10_000, now(), $expense,
    ))->toThrow(InvalidArgumentException::class);

    expect(fn () => app(CreateTransaction::class)->handle(
        $company, TransactionType::Expense, $wallet, 10_000, now(), $income,
    ))->toThrow(InvalidArgumentException::class);
});

test('balances stay consistent after fifty randomized operations', function () {
    [$user, $company, $bank, $income, $expense] = setupBooks();
    $cash = $company->wallets()->where('name', 'Cash')->firstOrFail();

    app(CreateTransaction::class)->handle($company, TransactionType::CapitalInvestment, $bank, 10_000_000, now());

    $transactions = [];

    foreach (range(1, 50) as $i) {
        $amount = random_int(100, 200_000);
        $wallet = random_int(0, 1) ? $bank : $cash;

        match (random_int(1, 6)) {
            1, 2 => $transactions[] = app(CreateTransaction::class)->handle($company, TransactionType::Income, $wallet, $amount, now(), $income),
            3, 4 => $transactions[] = app(CreateTransaction::class)->handle($company, TransactionType::Expense, $wallet, $amount, now(), $expense),
            5 => $transactions[] = app(CreateTransfer::class)->handle($company, $wallet, $wallet->is($bank) ? $cash : $bank, $amount, now()),
            6 => (function () use (&$transactions) {
                if ($transactions === []) {
                    return;
                }

                $candidate = $transactions[array_rand($transactions)]->refresh();

                if ($candidate->isPosted()) {
                    app(VoidTransaction::class)->handle($candidate);
                }
            })(),
        };
    }

    $this->artisan('moneta:verify-balances')->assertSuccessful();

    expect($bank->refresh()->cached_balance)->toBe($bank->derivedBalance())
        ->and($cash->refresh()->cached_balance)->toBe($cash->derivedBalance());
});

test('the transactions index reports filtered totals excluding transfers', function () {
    [$user, $company, $wallet, $income, $expense] = setupBooks();
    $cash = $company->wallets()->where('name', 'Cash')->firstOrFail();

    app(CreateTransaction::class)->handle($company, TransactionType::Income, $wallet, 100_000, now(), $income, creator: $user);
    app(CreateTransaction::class)->handle($company, TransactionType::Expense, $wallet, 40_000, now(), $expense, creator: $user);
    app(CreateTransfer::class)->handle($company, $wallet, $cash, 20_000, now(), creator: $user);

    $this->actingAs($user)
        ->get(route('transactions.index', ['current_company' => $company->slug]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('transactions/index')
            ->where('totals.in', 100_000)
            ->where('totals.out', 40_000)
            ->where('totals.net', 60_000));

    $this->actingAs($user)
        ->get(route('transactions.index', ['current_company' => $company->slug, 'type' => 'expense']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('totals.in', 0)
            ->where('totals.out', 40_000)
            ->where('totals.net', -40_000));
});
