<?php

declare(strict_types=1);

namespace App\Actions\Wallets;

use App\Models\Wallet;

final class ArchiveWallet
{
    public function handle(Wallet $wallet): Wallet
    {
        $wallet->update(['archived_at' => $wallet->isArchived() ? null : now()]);

        return $wallet;
    }
}
