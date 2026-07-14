<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Actions\Transactions\CreateTransfer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\SaveTransferRequest;
use App\Models\Company;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

final class TransferController extends Controller
{
    public function store(SaveTransferRequest $request, Company $current_company, CreateTransfer $createTransfer): RedirectResponse
    {
        $createTransfer->handle(
            $current_company,
            Wallet::query()->forCompany($current_company)->whereKey($request->validated('wallet_id'))->firstOrFail(),
            Wallet::query()->forCompany($current_company)->whereKey($request->validated('counter_wallet_id'))->firstOrFail(),
            $request->validated('amount'),
            Carbon::parse($request->validated('date')),
            $request->validated('description'),
            $request->validated('reference'),
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transfer recorded.')]);

        return back();
    }
}
