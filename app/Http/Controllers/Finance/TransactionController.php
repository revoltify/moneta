<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Actions\Budgets\EvaluateBudgetAlert;
use App\Actions\Transactions\CreateTransaction;
use App\Actions\Transactions\UpdateTransaction;
use App\Actions\Transactions\VoidTransaction;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\SaveTransactionRequest;
use App\Models\Category;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\AuditLogger;
use App\Support\TransactionFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

final class TransactionController extends Controller
{
    public function index(Request $request, Company $current_company): Response
    {
        $transactions = $this->filteredQuery($request, $current_company)
            ->with(['wallet', 'counterWallet', 'category'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $totalIn = (int) $this->filteredQuery($request, $current_company)
            ->whereIn('type', [TransactionType::Income, TransactionType::CapitalInvestment])
            ->toBase()
            ->sum('amount');
        $totalOut = (int) $this->filteredQuery($request, $current_company)
            ->whereIn('type', [TransactionType::Expense, TransactionType::CapitalWithdrawal])
            ->toBase()
            ->sum('amount');

        return Inertia::render('transactions/index', [
            'transactions' => collect($transactions->items())->map(fn (Transaction $transaction) => $this->payload($transaction)),
            'pagination' => [
                'currentPage' => $transactions->currentPage(),
                'lastPage' => $transactions->lastPage(),
                'total' => $transactions->total(),
            ],
            'totals' => [
                'in' => $totalIn,
                'out' => $totalOut,
                'net' => $totalIn - $totalOut,
            ],
            'filters' => $request->only(['type', 'wallet', 'category', 'from', 'to', 'search', 'status']),
            'wallets' => $current_company->wallets()->active()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Wallet $wallet) => ['id' => $wallet->id, 'name' => $wallet->name]),
            'categories' => $current_company->categories()->active()->orderBy('name')->get()
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'kind' => $category->kind->value,
                    'parentId' => $category->parent_id,
                ]),
        ]);
    }

    public function store(SaveTransactionRequest $request, Company $current_company, CreateTransaction $createTransaction, EvaluateBudgetAlert $evaluateBudgetAlert): RedirectResponse
    {
        $category = $request->validated('category_id')
            ? Category::query()->forCompany($current_company)->whereKey($request->validated('category_id'))->firstOrFail()
            : null;

        $transaction = $createTransaction->handle(
            $current_company,
            TransactionType::from($request->validated('type')),
            Wallet::query()->forCompany($current_company)->whereKey($request->validated('wallet_id'))->firstOrFail(),
            $request->validated('amount'),
            Carbon::parse($request->validated('date')),
            $category,
            $request->validated('description'),
            $request->validated('reference'),
            $request->user(),
        );

        AuditLogger::log($current_company, $request->user(), 'created', $transaction, [
            'type' => $transaction->type->value,
            'amount' => $transaction->amount,
            'wallet' => $transaction->wallet->name,
        ]);

        $alert = $transaction->type === TransactionType::Expense && $category !== null
            ? $evaluateBudgetAlert->handle($current_company, $category, $transaction->date)
            : null;

        Inertia::flash('toast', $alert !== null
            ? ['type' => $alert['level'], 'message' => $alert['message']]
            : ['type' => 'success', 'message' => __('Transaction recorded.')]);

        return back();
    }

    public function update(SaveTransactionRequest $request, Company $current_company, Transaction $transaction, UpdateTransaction $updateTransaction): RedirectResponse
    {

        $previousAmount = $transaction->amount;

        $updateTransaction->handle(
            $transaction,
            Wallet::query()->forCompany($current_company)->whereKey($request->validated('wallet_id'))->firstOrFail(),
            $request->validated('amount'),
            Carbon::parse($request->validated('date')),
            $request->validated('category_id')
                ? Category::query()->forCompany($current_company)->whereKey($request->validated('category_id'))->firstOrFail()
                : null,
            $request->validated('description'),
            $request->validated('reference'),
            $request->user(),
        );

        AuditLogger::log($current_company, $request->user(), 'updated', $transaction, [
            'amount' => ['from' => $previousAmount, 'to' => $transaction->refresh()->amount],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction updated.')]);

        return back();
    }

    public function destroy(Request $request, Company $current_company, Transaction $transaction, VoidTransaction $voidTransaction): RedirectResponse
    {

        $voidTransaction->handle($transaction);

        AuditLogger::log($current_company, $request->user(), 'voided', $transaction, [
            'type' => $transaction->type->value,
            'amount' => $transaction->amount,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction voided.')]);

        return back();
    }

    /**
     * @return Builder<Transaction>
     */
    private function filteredQuery(Request $request, Company $current_company)
    {
        return TransactionFilters::apply($current_company, [
            'type' => $request->string('type')->toString(),
            'wallet_id' => $request->string('wallet')->toString(),
            'category_id' => $request->string('category')->toString(),
            'from' => $request->string('from')->toString(),
            'to' => $request->string('to')->toString(),
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'type' => $transaction->type->value,
            'typeLabel' => $transaction->type->label(),
            'walletId' => $transaction->wallet_id,
            'walletName' => $transaction->wallet->name,
            'counterWalletId' => $transaction->counter_wallet_id,
            'counterWalletName' => $transaction->counterWallet?->name,
            'categoryId' => $transaction->category_id,
            'categoryName' => $transaction->category?->name,
            'categoryColor' => $transaction->category?->color,
            'amount' => $transaction->amount,
            'signedAmount' => $transaction->signedAmount(),
            'currency' => $transaction->currency,
            'date' => $transaction->date->toDateString(),
            'description' => $transaction->description,
            'reference' => $transaction->reference,
            'voided' => ! $transaction->isPosted(),
        ];
    }
}
