<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Budgets\EvaluateBudgetAlert;
use App\Actions\Transactions\CreateTransaction;
use App\Enums\TransactionType;
use App\Mcp\Concerns\InteractsWithCompany;
use App\Support\AuditLogger;
use App\Support\Money;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class RecordTransaction extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'Record an income, expense, capital investment or capital withdrawal. Amount is a decimal string in the company currency, e.g. "1500.50". Income/expense require a category of the matching kind.';

    public function __construct(
        private CreateTransaction $createTransaction,
        private EvaluateBudgetAlert $evaluateBudgetAlert,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'type' => $schema->string()->enum(['income', 'expense', 'capital_investment', 'capital_withdrawal'])->required(),
            'wallet' => $schema->string()->description('Wallet id or name.')->required(),
            'amount' => $schema->string()->description('Decimal amount in the company currency, e.g. "1500.50".')->required(),
            'date' => $schema->string()->description('YYYY-MM-DD. Defaults to today in the company timezone.'),
            'category' => $schema->string()->description('Category id or name. Required for income and expense.'),
            'description' => $schema->string()->description('What this transaction is for.'),
            'reference' => $schema->string()->description('External reference number.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate([
            'type' => 'required|in:income,expense,capital_investment,capital_withdrawal',
            'wallet' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'nullable|date',
            'description' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:100',
        ]);

        $company = $this->company($request);

        $type = TransactionType::from((string) $request->get('type'));
        $wallet = $this->wallet($company, $request->get('wallet'));
        $category = $type->requiresCategory()
            ? $this->category($company, (string) $request->get('category', ''), $type->categoryKind())
            : null;

        $date = $request->get('date') !== null
            ? Carbon::parse((string) $request->get('date'))
            : Carbon::parse(now($company->timezone)->toDateString());

        try {
            $transaction = $this->createTransaction->handle(
                $company,
                $type,
                $wallet,
                Money::toMinorUnits((string) $request->get('amount')),
                $date,
                $category,
                $request->get('description'),
                $request->get('reference'),
                $this->authenticatedUser($request),
            );
        } catch (InvalidArgumentException $exception) {
            return Response::error($exception->getMessage());
        }

        AuditLogger::log($company, $this->authenticatedUser($request), 'created', $transaction, [
            'type' => $transaction->type->value,
            'amount' => $transaction->amount,
            'wallet' => $wallet->name,
            'via' => 'mcp',
        ]);

        $alert = $type === TransactionType::Expense && $category !== null
            ? $this->evaluateBudgetAlert->handle($company, $category, $transaction->date)
            : null;

        $message = sprintf(
            'Recorded %s of %s in wallet "%s" on %s (transaction #%d). New wallet balance: %s.',
            $type->label(),
            Money::format($transaction->amount, $wallet->currency),
            $wallet->name,
            $transaction->date->toDateString(),
            $transaction->id,
            Money::format($wallet->refresh()->cached_balance, $wallet->currency),
        );

        if ($alert !== null) {
            $message .= ' Budget alert: '.$alert['message'];
        }

        return Response::text($message);
    }
}
