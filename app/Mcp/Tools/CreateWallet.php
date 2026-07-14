<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Wallets\CreateWallet as CreateWalletAction;
use App\Enums\WalletType;
use App\Mcp\Concerns\InteractsWithCompany;
use App\Support\Money;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class CreateWallet extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'Create a wallet (money account) like a bank account, mobile wallet, card or cash.';

    public function __construct(private CreateWalletAction $createWallet) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'name' => $schema->string()->description('Wallet name, unique within the company.')->required(),
            'type' => $schema->string()->enum(['bank', 'mobile_banking', 'cash', 'card', 'savings'])->required(),
            'opening_balance' => $schema->string()->description('Decimal opening balance in the wallet currency. Defaults to 0.'),
            'currency' => $schema->string()->enum(Money::codes())->description('Defaults to the company currency.'),
            'account_number' => $schema->string()->description('Account number (optional).'),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:bank,mobile_banking,cash,card,savings',
            'opening_balance' => 'nullable|numeric',
            'currency' => ['nullable', Rule::in(Money::codes())],
            'account_number' => 'nullable|string|max:50',
        ]);

        $company = $this->company($request);

        if ($company->wallets()->where('name', (string) $request->get('name'))->exists()) {
            return Response::error('A wallet with this name already exists in the company.');
        }

        $wallet = $this->createWallet->handle(
            $company,
            (string) $request->get('name'),
            WalletType::from((string) $request->get('type')),
            $request->get('account_number'),
            openingBalance: $request->get('opening_balance') !== null ? Money::toMinorUnits((string) $request->get('opening_balance')) : 0,
            creator: $this->authenticatedUser($request),
            currency: (string) $request->get('currency', $company->currency),
        );

        return Response::text(sprintf(
            'Wallet "%s" (#%d, %s) created with balance %s.',
            $wallet->name,
            $wallet->id,
            $wallet->type->label(),
            Money::format($wallet->cached_balance, $wallet->currency),
        ));
    }
}
