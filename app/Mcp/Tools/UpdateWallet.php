<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Wallets\UpdateWallet as UpdateWalletAction;
use App\Enums\WalletType;
use App\Mcp\Concerns\InteractsWithCompany;
use App\Support\Money;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class UpdateWallet extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'Edit a wallet. Only pass the fields you want to change. Changing the opening balance shifts the current balance by the difference.';

    public function __construct(private UpdateWalletAction $updateWallet) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'wallet' => $schema->string()->description('Wallet id or name.')->required(),
            'name' => $schema->string()->description('New name.'),
            'type' => $schema->string()->enum(['bank', 'mobile_banking', 'cash', 'card', 'savings']),
            'opening_balance' => $schema->string()->description('New decimal opening balance.'),
            'account_number' => $schema->string()->description('New account number.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate([
            'wallet' => 'required',
            'name' => 'nullable|string|max:100',
            'type' => 'nullable|in:bank,mobile_banking,cash,card,savings',
            'opening_balance' => 'nullable|numeric',
            'account_number' => 'nullable|string|max:50',
        ]);

        $company = $this->company($request);

        $wallet = $this->wallet($company, $request->get('wallet'));

        $newName = (string) $request->get('name', $wallet->name);

        if ($newName !== $wallet->name && $company->wallets()->where('name', $newName)->exists()) {
            return Response::error('A wallet with this name already exists in the company.');
        }

        $this->updateWallet->handle(
            $wallet,
            $newName,
            $request->get('type') !== null ? WalletType::from((string) $request->get('type')) : $wallet->type,
            $request->get('account_number', $wallet->account_number),
            $wallet->icon,
            $wallet->color,
            $request->get('opening_balance') !== null ? Money::toMinorUnits((string) $request->get('opening_balance')) : null,
        );

        $wallet->refresh();

        return Response::text(sprintf(
            'Wallet #%d updated: "%s" (%s), balance %s.',
            $wallet->id,
            $wallet->name,
            $wallet->type->label(),
            Money::format($wallet->cached_balance, $wallet->currency),
        ));
    }
}
