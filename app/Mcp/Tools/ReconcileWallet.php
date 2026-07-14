<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Wallets\ReconcileWallet as ReconcileWalletAction;
use App\Mcp\Concerns\InteractsWithCompany;
use App\Support\AuditLogger;
use App\Support\Money;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class ReconcileWallet extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'Reconcile a wallet to its actual real-world balance. Posts a balance-adjustment income or expense for the difference.';

    public function __construct(private ReconcileWalletAction $reconcileWallet) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'wallet' => $schema->string()->description('Wallet id or name.')->required(),
            'actual_balance' => $schema->string()->description('The real decimal balance, e.g. "25000.00".')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate([
            'wallet' => 'required',
            'actual_balance' => 'required|numeric',
        ]);

        $company = $this->company($request);

        $wallet = $this->wallet($company, $request->get('wallet'));

        $transaction = $this->reconcileWallet->handle(
            $wallet,
            Money::toMinorUnits((string) $request->get('actual_balance')),
            $this->authenticatedUser($request),
        );

        AuditLogger::log($company, $this->authenticatedUser($request), 'reconciled', $wallet, [
            'adjustment' => $transaction->amount ?? 0,
            'via' => 'mcp',
        ]);

        if ($transaction === null) {
            return Response::text(sprintf('Wallet "%s" already matches — nothing to adjust.', $wallet->name));
        }

        return Response::text(sprintf(
            'Adjustment of %s posted to wallet "%s". New balance: %s.',
            Money::format($transaction->amount, $wallet->currency),
            $wallet->name,
            Money::format($wallet->refresh()->cached_balance, $wallet->currency),
        ));
    }
}
