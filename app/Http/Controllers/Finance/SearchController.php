<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SearchController extends Controller
{
    public function index(Request $request, Company $current_company): JsonResponse
    {
        $query = trim($request->string('q')->toString());

        if (mb_strlen($query) < 2) {
            return response()->json([
                'transactions' => [],
                'wallets' => [],
                'categories' => [],
            ]);
        }

        return response()->json([
            'transactions' => $current_company->transactions()
                ->with(['wallet', 'category'])
                ->where(fn ($inner) => $inner
                    ->where('description', 'like', "%{$query}%")
                    ->orWhere('reference', 'like', "%{$query}%"))
                ->orderByDesc('date')
                ->limit(8)
                ->get()
                ->map(fn (Transaction $transaction) => [
                    'id' => $transaction->id,
                    'description' => $transaction->description ?? $transaction->type->label(),
                    'walletName' => $transaction->wallet->name,
                    'signedAmount' => $transaction->signedAmount(),
                    'amount' => $transaction->amount,
                    'type' => $transaction->type->value,
                    'date' => $transaction->date->toDateString(),
                    'voided' => ! $transaction->isPosted(),
                ]),
            'wallets' => $current_company->wallets()
                ->where('name', 'like', "%{$query}%")
                ->orderBy('name')
                ->limit(5)
                ->get()
                ->map(fn (Wallet $wallet) => [
                    'id' => $wallet->id,
                    'name' => $wallet->name,
                    'typeLabel' => $wallet->type->label(),
                    'balance' => $wallet->cached_balance,
                    'currency' => $wallet->currency,
                ]),
            'categories' => $current_company->categories()
                ->where('name', 'like', "%{$query}%")
                ->orderBy('name')
                ->limit(5)
                ->get()
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'kindLabel' => $category->kind->label(),
                ]),
        ]);
    }
}
