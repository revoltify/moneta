<?php

declare(strict_types=1);

namespace App\Actions\Wallets;

use App\Enums\WalletType;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

final class UpdateWallet
{
    public function handle(
        Wallet $wallet,
        string $name,
        WalletType $type,
        ?string $accountNumber = null,
        ?string $icon = null,
        ?string $color = null,
        ?int $openingBalance = null,
    ): Wallet {
        return DB::transaction(function () use ($wallet, $name, $type, $accountNumber, $icon, $color, $openingBalance) {
            $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $attributes = [
                'name' => $name,
                'type' => $type,
                'account_number' => $accountNumber,
                'icon' => $icon,
                'color' => $color,
            ];

            if ($openingBalance !== null && $openingBalance !== $wallet->opening_balance) {
                $attributes['opening_balance'] = $openingBalance;
                $attributes['cached_balance'] = $wallet->cached_balance + ($openingBalance - $wallet->opening_balance);
            }

            $wallet->update($attributes);

            return $wallet;
        });
    }
}
