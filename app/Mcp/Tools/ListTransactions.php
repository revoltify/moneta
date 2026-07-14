<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithCompany;
use App\Models\Transaction;
use App\Support\TransactionFilters;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
final class ListTransactions extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'List transactions of a company with optional filters. Returns 25 per page, newest first. Amounts are integers in minor units (1/100 of the currency).';

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'type' => $schema->string()->enum(['income', 'expense', 'transfer', 'capital_investment', 'capital_withdrawal']),
            'wallet' => $schema->string()->description('Wallet id or name.'),
            'category' => $schema->string()->description('Category id or name.'),
            'from' => $schema->string()->description('Start date YYYY-MM-DD.'),
            'to' => $schema->string()->description('End date YYYY-MM-DD.'),
            'search' => $schema->string()->description('Search in description and reference.'),
            'status' => $schema->string()->enum(['posted', 'voided'])->default('posted'),
            'page' => $schema->integer()->description('Page number.')->default(1),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate([
            'type' => 'nullable|in:income,expense,transfer,capital_investment,capital_withdrawal',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'status' => 'nullable|in:posted,voided',
            'page' => 'nullable|integer|min:1',
        ]);

        $company = $this->company($request);

        $walletId = $request->get('wallet') !== null ? (string) $this->wallet($company, $request->get('wallet'))->id : '';
        $categoryId = $request->get('category') !== null ? (string) $this->category($company, $request->get('category'))->id : '';

        $transactions = TransactionFilters::apply($company, [
            'type' => (string) $request->get('type', ''),
            'wallet_id' => $walletId,
            'category_id' => $categoryId,
            'from' => (string) $request->get('from', ''),
            'to' => (string) $request->get('to', ''),
            'search' => (string) $request->get('search', ''),
            'status' => (string) $request->get('status', ''),
        ])
            ->with(['wallet', 'counterWallet', 'category'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'page', (int) $request->get('page', 1));

        return Response::json([
            'company' => $company->slug,
            'page' => $transactions->currentPage(),
            'lastPage' => $transactions->lastPage(),
            'total' => $transactions->total(),
            'transactions' => collect($transactions->items())->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'type' => $transaction->type->value,
                'date' => $transaction->date->toDateString(),
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'wallet' => $transaction->wallet->name,
                'counterWallet' => $transaction->counterWallet?->name,
                'category' => $transaction->category?->name,
                'description' => $transaction->description,
                'reference' => $transaction->reference,
                'voided' => ! $transaction->isPosted(),
            ]),
        ]);
    }
}
