<?php

namespace App\Console\Commands;

use App\Models\Wallet;
use Illuminate\Console\Command;

class VerifyBalances extends Command
{
    protected $signature = 'moneta:verify-balances';

    protected $description = 'Verify every wallet cached balance matches the balance derived from its transactions';

    public function handle(): int
    {
        $failures = 0;

        foreach (Wallet::query()->with('company')->cursor() as $wallet) {
            $derived = $wallet->derivedBalance();

            if ($wallet->cached_balance !== $derived) {
                $this->error("Wallet {$wallet->id} ({$wallet->name}) cached balance {$wallet->cached_balance} != derived {$derived}.");
                $failures++;
            }
        }

        if ($failures > 0) {
            $this->error("Balance verification failed with {$failures} issue(s).");

            return self::FAILURE;
        }

        $this->info('Balances verified: every wallet reconciles with its transaction history.');

        return self::SUCCESS;
    }
}
