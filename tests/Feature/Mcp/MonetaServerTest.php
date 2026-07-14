<?php

declare(strict_types=1);

use App\Actions\Companies\CreateCompany;
use App\Actions\Transactions\CreateTransaction;
use App\Enums\TransactionType;
use App\Mcp\Servers\MonetaServer;
use App\Mcp\Tools\CreateWallet;
use App\Mcp\Tools\GetIncomeStatement;
use App\Mcp\Tools\ListTransactions;
use App\Mcp\Tools\RecordTransaction;
use App\Mcp\Tools\RecordTransfer;
use App\Mcp\Tools\SetBudget;
use App\Mcp\Tools\VoidTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\ClientRepository;

beforeEach(function () {
    Artisan::call('passport:keys');
});

function mcpCompany(): array
{
    $user = User::factory()->create();
    $company = app(CreateCompany::class)->handle($user, 'Acme Studio');
    $user->switchCompany($company);

    return [$user, $company];
}

test('record-transaction records an expense and updates the wallet balance', function () {
    [$user, $company] = mcpCompany();
    $bank = $company->wallets()->where('name', 'Bank')->firstOrFail();

    $response = MonetaServer::actingAs($user)->tool(RecordTransaction::class, [
        'type' => 'expense',
        'wallet' => 'Bank',
        'amount' => '1500.50',
        'category' => 'Marketing',
        'description' => 'Facebook ads',
    ]);

    $response->assertOk()->assertSee('Recorded');

    expect($bank->refresh()->cached_balance)->toBe(-150_050)
        ->and($company->transactions()->count())->toBe(1);
});

test('record-transaction rejects a category of the wrong kind', function () {
    [$user] = mcpCompany();

    $response = MonetaServer::actingAs($user)->tool(RecordTransaction::class, [
        'type' => 'income',
        'wallet' => 'Bank',
        'amount' => '100',
        'category' => 'Marketing',
    ]);

    $response->assertHasErrors();
});

test('record-transfer moves money between wallets', function () {
    [$user, $company] = mcpCompany();
    $bank = $company->wallets()->where('name', 'Bank')->firstOrFail();
    $cash = $company->wallets()->where('name', 'Cash')->firstOrFail();

    $response = MonetaServer::actingAs($user)->tool(RecordTransfer::class, [
        'from_wallet' => 'Bank',
        'to_wallet' => 'Cash',
        'amount' => '1000',
    ]);

    $response->assertOk()->assertSee('Transferred');

    expect($bank->refresh()->cached_balance)->toBe(-100_000)
        ->and($cash->refresh()->cached_balance)->toBe(100_000);
});

test('void-transaction restores the wallet balance', function () {
    [$user, $company] = mcpCompany();
    $bank = $company->wallets()->where('name', 'Bank')->firstOrFail();
    $income = $company->categories()->where('kind', 'income')->whereNull('parent_id')->firstOrFail();

    $transaction = app(CreateTransaction::class)->handle(
        $company, TransactionType::Income, $bank, 50_000, now(), $income, creator: $user,
    );

    $response = MonetaServer::actingAs($user)->tool(VoidTransaction::class, [
        'id' => $transaction->id,
    ]);

    $response->assertOk()->assertSee('voided');

    expect($bank->refresh()->cached_balance)->toBe(0);
});

test('get-income-statement reflects recorded transactions', function () {
    [$user, $company] = mcpCompany();
    $bank = $company->wallets()->where('name', 'Bank')->firstOrFail();
    $income = $company->categories()->where('kind', 'income')->whereNull('parent_id')->firstOrFail();
    $expense = $company->categories()->where('kind', 'expense')->whereNull('parent_id')->firstOrFail();

    app(CreateTransaction::class)->handle($company, TransactionType::Income, $bank, 200_000, now(), $income, creator: $user);
    app(CreateTransaction::class)->handle($company, TransactionType::Expense, $bank, 75_000, now(), $expense, creator: $user);

    $response = MonetaServer::actingAs($user)->tool(GetIncomeStatement::class, []);

    $response->assertOk()
        ->assertSee('"totalIncome":200000')
        ->assertSee('"totalExpense":75000')
        ->assertSee('"netProfit":125000');
});

test('list-transactions filters by wallet name', function () {
    [$user, $company] = mcpCompany();
    $bank = $company->wallets()->where('name', 'Bank')->firstOrFail();
    $cash = $company->wallets()->where('name', 'Cash')->firstOrFail();
    $expense = $company->categories()->where('kind', 'expense')->whereNull('parent_id')->firstOrFail();

    app(CreateTransaction::class)->handle($company, TransactionType::Expense, $bank, 10_000, now(), $expense, creator: $user);
    app(CreateTransaction::class)->handle($company, TransactionType::Expense, $cash, 20_000, now(), $expense, creator: $user);

    $response = MonetaServer::actingAs($user)->tool(ListTransactions::class, [
        'wallet' => 'Cash',
    ]);

    $response->assertOk()->assertSee('"total":1')->assertSee('20000');
});

test('create-wallet and set-budget round-trip', function () {
    [$user, $company] = mcpCompany();

    MonetaServer::actingAs($user)->tool(CreateWallet::class, [
        'name' => 'Payroll Account',
        'type' => 'bank',
        'opening_balance' => '5000',
    ])->assertOk()->assertSee('Payroll Account');

    expect($company->wallets()->where('name', 'Payroll Account')->firstOrFail()->cached_balance)->toBe(500_000);

    MonetaServer::actingAs($user)->tool(SetBudget::class, [
        'category' => 'Marketing',
        'amount' => '10000',
        'period' => 'monthly',
    ])->assertOk()->assertSee('Monthly budget');

    expect($company->budgets()->count())->toBe(1);
});

test('the mcp web endpoint requires a token', function () {
    $this->postJson('/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'ping',
    ])->assertUnauthorized();
});

test('the mcp web endpoint responds with a valid personal access token', function () {
    [$user] = mcpCompany();

    app(ClientRepository::class)->createPersonalAccessGrantClient('Test Personal Client', 'users');
    $token = $user->createToken('test')->accessToken;

    $this->postJson('/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'ping',
    ], ['Authorization' => 'Bearer '.$token])->assertOk();
});

test('oauth discovery endpoints are advertised for mcp clients', function () {
    $this->getJson('/.well-known/oauth-protected-resource')->assertOk();
    $this->getJson('/.well-known/oauth-authorization-server')
        ->assertOk()
        ->assertJsonStructure(['authorization_endpoint', 'token_endpoint', 'registration_endpoint']);
});
