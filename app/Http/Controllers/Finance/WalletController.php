<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Actions\Wallets\ArchiveWallet;
use App\Actions\Wallets\CreateWallet;
use App\Actions\Wallets\ReconcileWallet;
use App\Actions\Wallets\UpdateWallet;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\SaveWalletRequest;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\AuditLogger;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class WalletController extends Controller
{
    public function index(Request $request, Company $current_company): Response
    {
        return Inertia::render('wallets/index', [
            'wallets' => $current_company->wallets()
                ->orderBy('archived_at')
                ->orderBy('name')
                ->get()
                ->map(fn (Wallet $wallet) => $this->walletPayload($wallet)),
            'walletTypes' => WalletType::options(),
        ]);
    }

    public function show(Request $request, Company $current_company, Wallet $wallet): Response
    {

        $entries = $wallet->company->transactions()
            ->posted()
            ->where(fn ($query) => $query
                ->where('wallet_id', $wallet->id)
                ->orWhere('counter_wallet_id', $wallet->id))
            ->with(['category', 'wallet', 'counterWallet'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $deltaFor = function (Transaction $transaction) use ($wallet): int {
            if ($transaction->type === TransactionType::Transfer) {
                return $transaction->wallet_id === $wallet->id
                    ? -$transaction->amount
                    : $transaction->amount;
            }

            return $transaction->signedAmount();
        };

        $newerDelta = $wallet->company->transactions()
            ->posted()
            ->where(fn ($query) => $query
                ->where('wallet_id', $wallet->id)
                ->orWhere('counter_wallet_id', $wallet->id))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit($entries->perPage() * ($entries->currentPage() - 1))
            ->get()
            ->sum($deltaFor);

        $running = $wallet->cached_balance - (int) $newerDelta;

        $ledger = collect($entries->items())->map(function (Transaction $transaction) use (&$running, $deltaFor) {
            $delta = $deltaFor($transaction);
            $row = [
                'id' => $transaction->id,
                'date' => $transaction->date->toDateString(),
                'description' => $transaction->description
                    ?? $transaction->category->name
                    ?? $transaction->type->label(),
                'debit' => max($delta, 0),
                'credit' => max(-$delta, 0),
                'balance' => $running,
            ];
            $running -= $delta;

            return $row;
        });

        return Inertia::render('wallets/show', [
            'wallet' => $this->walletPayload($wallet),
            'ledger' => $ledger,
            'pagination' => [
                'currentPage' => $entries->currentPage(),
                'lastPage' => $entries->lastPage(),
                'total' => $entries->total(),
            ],
            'walletTypes' => WalletType::options(),
        ]);
    }

    public function store(SaveWalletRequest $request, Company $current_company, CreateWallet $createWallet): RedirectResponse
    {
        $createWallet->handle(
            $current_company,
            $request->validated('name'),
            WalletType::from($request->validated('type')),
            $request->validated('account_number'),
            $request->validated('icon'),
            $request->validated('color'),
            $request->validated('opening_balance') ?? 0,
            $request->user(),
            $request->validated('currency') ?? $current_company->currency,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Wallet created.')]);

        return back();
    }

    public function update(SaveWalletRequest $request, Company $current_company, Wallet $wallet, UpdateWallet $updateWallet): RedirectResponse
    {

        $updateWallet->handle(
            $wallet,
            $request->validated('name'),
            WalletType::from($request->validated('type')),
            $request->validated('account_number'),
            $request->validated('icon'),
            $request->validated('color'),
            $request->validated('opening_balance'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Wallet updated.')]);

        return back();
    }

    public function archive(Request $request, Company $current_company, Wallet $wallet, ArchiveWallet $archiveWallet): RedirectResponse
    {

        $archiveWallet->handle($wallet);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $wallet->isArchived() ? __('Wallet archived.') : __('Wallet restored.'),
        ]);

        return back();
    }

    public function reconcile(Request $request, Company $current_company, Wallet $wallet, ReconcileWallet $reconcileWallet): RedirectResponse
    {

        $validated = $request->validate([
            'actual_balance' => ['required', 'numeric'],
        ]);

        $transaction = $reconcileWallet->handle(
            $wallet,
            Money::toMinorUnits((string) $validated['actual_balance']),
            $request->user(),
        );

        AuditLogger::log($current_company, $request->user(), 'reconciled', $wallet, [
            'adjustment' => $transaction->amount ?? 0,
        ]);

        Inertia::flash('toast', $transaction === null
            ? ['type' => 'info', 'message' => __('Balance already matches — nothing to adjust.')]
            : ['type' => 'success', 'message' => __('Adjustment of :amount posted.', ['amount' => Money::format($transaction->amount, $wallet->currency)])]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function walletPayload(Wallet $wallet): array
    {
        return [
            'id' => $wallet->id,
            'name' => $wallet->name,
            'type' => $wallet->type->value,
            'typeLabel' => $wallet->type->label(),
            'accountNumber' => $wallet->account_number,
            'icon' => $wallet->icon,
            'color' => $wallet->color,
            'currency' => $wallet->currency,
            'openingBalance' => $wallet->opening_balance,
            'balance' => $wallet->cached_balance,
            'archived' => $wallet->isArchived(),
        ];
    }
}
