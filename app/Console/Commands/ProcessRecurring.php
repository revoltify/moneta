<?php

namespace App\Console\Commands;

use App\Actions\Recurring\ProcessRecurringTransactions;
use Illuminate\Console\Command;

class ProcessRecurring extends Command
{
    protected $signature = 'moneta:process-recurring';

    protected $description = 'Create transactions for all recurring schedules that are due';

    public function handle(ProcessRecurringTransactions $processor): int
    {
        $count = $processor->handle();

        $this->info("Processed {$count} recurring transaction(s).");

        return self::SUCCESS;
    }
}
