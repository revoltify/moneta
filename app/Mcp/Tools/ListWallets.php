<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithCompany;
use App\Models\Wallet;
use App\Support\Money;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
final class ListWallets extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'List the wallets (money accounts) of a company with their current balances.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'include_archived' => $schema->boolean()->description('Include archived wallets.')->default(false),
        ];
    }

    public function handle(Request $request): Response
    {
        $company = $this->company($request);

        $wallets = $company->wallets()
            ->when($request->get('include_archived') !== true, fn ($query) => $query->active())
            ->orderBy('name')
            ->get()
            ->map(fn (Wallet $wallet) => [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'type' => $wallet->type->value,
                'currency' => $wallet->currency,
                'balance' => $wallet->cached_balance,
                'balanceFormatted' => Money::format($wallet->cached_balance, $wallet->currency),
                'archived' => $wallet->isArchived(),
            ]);

        return Response::json(['company' => $company->slug, 'wallets' => $wallets]);
    }
}
