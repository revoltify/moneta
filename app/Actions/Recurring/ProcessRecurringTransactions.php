<?php

declare(strict_types=1);

namespace App\Actions\Recurring;

use App\Actions\Transactions\CreateTransaction;
use App\Actions\Transactions\CreateTransfer;
use App\Enums\RecurrenceFrequency;
use App\Enums\TransactionType;
use App\Models\RecurringTransaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class ProcessRecurringTransactions
{
    private const int MAX_CATCH_UP_RUNS = 36;

    public function __construct(
        private CreateTransaction $createTransaction,
        private CreateTransfer $createTransfer,
    ) {}

    public function handle(?CarbonInterface $today = null): int
    {
        $processed = 0;

        $due = RecurringTransaction::query()
            ->with('company')
            ->where('is_active', true)
            ->whereDate('next_run_on', '<=', ($today ?? now()->addDay())->toDateString())
            ->get();

        foreach ($due as $recurring) {
            $companyToday = $today ?? Carbon::parse(now($recurring->company->timezone)->toDateString());

            if (Carbon::parse($recurring->next_run_on)->greaterThan($companyToday)) {
                continue;
            }

            $processed += DB::transaction(fn () => $this->processOne($recurring, $companyToday));
        }

        return $processed;
    }

    private function processOne(RecurringTransaction $recurring, CarbonInterface $today): int
    {
        $runs = 0;
        $runDate = Carbon::parse($recurring->next_run_on);

        while ($runDate->lessThanOrEqualTo($today) && $runs < self::MAX_CATCH_UP_RUNS) {
            if ($recurring->ends_on !== null && $runDate->greaterThan($recurring->ends_on)) {
                $recurring->update(['is_active' => false]);
                break;
            }

            $this->execute($recurring, $runDate);

            $runs++;
            $runDate = $this->advance($recurring, $runDate);

            $recurring->update([
                'last_run_on' => $recurring->next_run_on,
                'next_run_on' => $runDate->toDateString(),
            ]);
        }

        return $runs;
    }

    private function execute(RecurringTransaction $recurring, CarbonInterface $date): void
    {
        if ($recurring->type === TransactionType::Transfer) {
            $counterWallet = $recurring->counterWallet;

            if ($counterWallet === null) {
                return;
            }

            $this->createTransfer->handle(
                $recurring->company,
                $recurring->wallet,
                $counterWallet,
                $recurring->amount,
                $date,
                $recurring->description ?? $recurring->name,
            );

            return;
        }

        $this->createTransaction->handle(
            $recurring->company,
            $recurring->type,
            $recurring->wallet,
            $recurring->amount,
            $date,
            $recurring->category,
            $recurring->description ?? $recurring->name,
        );
    }

    private function advance(RecurringTransaction $recurring, CarbonInterface $from): Carbon
    {
        $interval = max(1, $recurring->interval);
        $next = Carbon::parse($from);

        return match ($recurring->frequency) {
            RecurrenceFrequency::Daily => $next->addDays($interval),
            RecurrenceFrequency::Weekly => $next->addWeeks($interval),
            RecurrenceFrequency::Monthly => $this->advanceMonthly($next, $interval, $recurring->day_of_month),
            RecurrenceFrequency::Yearly => $next->addYearsNoOverflow($interval),
        };
    }

    private function advanceMonthly(Carbon $from, int $interval, ?int $dayOfMonth): Carbon
    {
        $next = $from->addMonthsNoOverflow($interval);

        if ($dayOfMonth !== null) {
            $next = $next->setDay(min($dayOfMonth, $next->daysInMonth()));
        }

        return $next;
    }
}
