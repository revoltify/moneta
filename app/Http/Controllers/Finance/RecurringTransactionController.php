<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Enums\RecurrenceFrequency;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\SaveRecurringTransactionRequest;
use App\Models\Company;
use App\Models\RecurringTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

final class RecurringTransactionController extends Controller
{
    public function index(Request $request, Company $current_company): Response
    {
        return Inertia::render('recurring/index', [
            'recurring' => RecurringTransaction::query()
                ->forCompany($current_company)
                ->with(['wallet', 'counterWallet', 'category'])
                ->orderBy('next_run_on')
                ->get()
                ->map(fn (RecurringTransaction $recurring) => [
                    'id' => $recurring->id,
                    'name' => $recurring->name,
                    'type' => $recurring->type->value,
                    'typeLabel' => $recurring->type->label(),
                    'walletId' => $recurring->wallet_id,
                    'walletName' => $recurring->wallet->name,
                    'counterWalletId' => $recurring->counter_wallet_id,
                    'counterWalletName' => $recurring->counterWallet?->name,
                    'categoryId' => $recurring->category_id,
                    'categoryName' => $recurring->category?->name,
                    'amount' => $recurring->amount,
                    'description' => $recurring->description,
                    'frequency' => $recurring->frequency->value,
                    'frequencyLabel' => $recurring->frequency->label(),
                    'interval' => $recurring->interval,
                    'startsOn' => $recurring->starts_on->toDateString(),
                    'endsOn' => $recurring->ends_on?->toDateString(),
                    'nextRunOn' => $recurring->next_run_on->toDateString(),
                    'lastRunOn' => $recurring->last_run_on?->toDateString(),
                    'active' => $recurring->is_active,
                ]),
            'wallets' => $current_company->wallets()->active()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($wallet) => ['id' => $wallet->id, 'name' => $wallet->name]),
            'categories' => $current_company->categories()->active()->orderBy('name')->get()
                ->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'kind' => $category->kind->value,
                    'parentId' => $category->parent_id,
                ]),
            'frequencies' => RecurrenceFrequency::options(),
        ]);
    }

    public function store(SaveRecurringTransactionRequest $request, Company $current_company): RedirectResponse
    {
        $startsOn = Carbon::parse($request->validated('starts_on'));

        RecurringTransaction::create([
            'company_id' => $current_company->id,
            'name' => $request->validated('name'),
            'type' => TransactionType::from($request->validated('type')),
            'wallet_id' => $request->validated('wallet_id'),
            'counter_wallet_id' => $request->validated('counter_wallet_id'),
            'category_id' => $request->validated('category_id'),
            'amount' => $request->validated('amount'),
            'description' => $request->validated('description'),
            'frequency' => RecurrenceFrequency::from($request->validated('frequency')),
            'interval' => $request->validated('interval'),
            'day_of_month' => $request->validated('frequency') === 'monthly' ? $startsOn->day : null,
            'starts_on' => $startsOn->toDateString(),
            'ends_on' => $request->validated('ends_on'),
            'next_run_on' => $startsOn->toDateString(),
            'is_active' => true,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Recurring transaction created.')]);

        return back();
    }

    public function toggle(Request $request, Company $current_company, RecurringTransaction $recurring_transaction): RedirectResponse
    {

        $recurring_transaction->update(['is_active' => ! $recurring_transaction->is_active]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $recurring_transaction->is_active ? __('Recurring transaction resumed.') : __('Recurring transaction paused.'),
        ]);

        return back();
    }

    public function destroy(Request $request, Company $current_company, RecurringTransaction $recurring_transaction): RedirectResponse
    {

        $recurring_transaction->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Recurring transaction deleted.')]);

        return back();
    }
}
