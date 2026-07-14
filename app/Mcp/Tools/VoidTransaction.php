<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Transactions\VoidTransaction as VoidTransactionAction;
use App\Mcp\Concerns\InteractsWithCompany;
use App\Models\Transaction;
use App\Support\AuditLogger;
use App\Support\Money;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
final class VoidTransaction extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'Void a posted transaction. The wallet balance is restored. Voided transactions cannot be un-voided.';

    public function __construct(private VoidTransactionAction $voidTransaction) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'id' => $schema->integer()->description('Transaction id.')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate(['id' => 'required|integer']);

        $company = $this->company($request);

        $transaction = Transaction::query()->forCompany($company)->whereKey((int) $request->get('id'))->first();

        if ($transaction === null) {
            throw ValidationException::withMessages(['id' => 'Transaction not found in this company.']);
        }

        try {
            $this->voidTransaction->handle($transaction);
        } catch (InvalidArgumentException $exception) {
            return Response::error($exception->getMessage());
        }

        AuditLogger::log($company, $this->authenticatedUser($request), 'voided', $transaction, [
            'type' => $transaction->type->value,
            'amount' => $transaction->amount,
            'via' => 'mcp',
        ]);

        return Response::text(sprintf(
            'Transaction #%d (%s of %s) voided. Wallet balances restored.',
            $transaction->id,
            $transaction->type->label(),
            Money::format($transaction->amount, $transaction->currency),
        ));
    }
}
