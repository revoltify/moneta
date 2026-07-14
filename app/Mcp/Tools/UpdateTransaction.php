<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Transactions\UpdateTransaction as UpdateTransactionAction;
use App\Mcp\Concerns\InteractsWithCompany;
use App\Models\Transaction;
use App\Support\AuditLogger;
use App\Support\Money;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class UpdateTransaction extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'Edit a posted non-transfer transaction. Only pass the fields you want to change; omitted fields keep their current value.';

    public function __construct(private UpdateTransactionAction $updateTransaction) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'id' => $schema->integer()->description('Transaction id.')->required(),
            'wallet' => $schema->string()->description('New wallet id or name.'),
            'amount' => $schema->string()->description('New decimal amount in the company currency.'),
            'date' => $schema->string()->description('New date YYYY-MM-DD.'),
            'category' => $schema->string()->description('New category id or name.'),
            'description' => $schema->string()->description('New description.'),
            'reference' => $schema->string()->description('New reference.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate([
            'id' => 'required|integer',
            'amount' => 'nullable|numeric|min:0.01',
            'date' => 'nullable|date',
            'description' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:100',
        ]);

        $company = $this->company($request);

        $transaction = Transaction::query()->forCompany($company)->whereKey((int) $request->get('id'))->first();

        if ($transaction === null) {
            throw ValidationException::withMessages(['id' => 'Transaction not found in this company.']);
        }

        $wallet = $request->get('wallet') !== null
            ? $this->wallet($company, $request->get('wallet'))
            : $transaction->wallet;

        $category = $request->get('category') !== null
            ? $this->category($company, $request->get('category'), $transaction->type->categoryKind())
            : $transaction->category;

        $previousAmount = $transaction->amount;

        try {
            $this->updateTransaction->handle(
                $transaction,
                $wallet,
                $request->get('amount') !== null ? Money::toMinorUnits((string) $request->get('amount')) : $transaction->amount,
                $request->get('date') !== null ? Carbon::parse((string) $request->get('date')) : Carbon::parse($transaction->date->toDateString()),
                $category,
                $request->get('description', $transaction->description),
                $request->get('reference', $transaction->reference),
                $this->authenticatedUser($request),
            );
        } catch (InvalidArgumentException $exception) {
            return Response::error($exception->getMessage());
        }

        AuditLogger::log($company, $this->authenticatedUser($request), 'updated', $transaction, [
            'amount' => ['from' => $previousAmount, 'to' => $transaction->refresh()->amount],
            'via' => 'mcp',
        ]);

        return Response::text(sprintf(
            'Transaction #%d updated: %s on %s in wallet "%s".',
            $transaction->id,
            Money::format($transaction->amount, $transaction->currency),
            $transaction->date->toDateString(),
            $transaction->wallet->name,
        ));
    }
}
