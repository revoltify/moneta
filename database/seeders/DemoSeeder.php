<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Budgets\EvaluateBudgetAlert;
use App\Actions\Companies\CreateCompany;
use App\Actions\Transactions\CreateTransaction;
use App\Actions\Transactions\CreateTransfer;
use App\Enums\RecurrenceFrequency;
use App\Enums\TransactionType;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Company;
use App\Models\RecurringTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::factory()->create([
            'name' => 'Demo Owner',
            'email' => 'demo@example.com',
        ]);

        $company = app(CreateCompany::class)->handle($owner, 'Acme Studio');

        $bank = $this->wallet($company, 'Bank');
        $mobile = $this->wallet($company, 'Mobile Wallet');
        $card = $this->wallet($company, 'Card');
        $cash = $this->wallet($company, 'Cash');

        $commission = $this->category($company, 'Sales');
        $setupFee = $this->category($company, 'Services');
        $hosting = $this->category($company, 'Software & Hosting');
        $marketing = $this->category($company, 'Marketing');
        $salaries = $this->category($company, 'Salaries');
        $officeRent = $this->category($company, 'Office Rent');
        $bankCharges = $this->category($company, 'Bank Charges');

        $create = app(CreateTransaction::class);
        $transfer = app(CreateTransfer::class);

        $create->handle($company, TransactionType::CapitalInvestment, $bank, 50_000_000, now()->subMonths(3)->startOfMonth(), description: 'Initial capital', creator: $owner);
        $transfer->handle($company, $bank, $mobile, 5_000_000, now()->subMonths(3)->startOfMonth()->addDay(), 'Mobile wallet float', creator: $owner);
        $transfer->handle($company, $bank, $card, 3_000_000, now()->subMonths(3)->startOfMonth()->addDay(), 'Card float', creator: $owner);
        $transfer->handle($company, $bank, $cash, 500_000, now()->subMonths(3)->startOfMonth()->addDays(2), 'Petty cash', creator: $owner);

        foreach (range(2, 0) as $monthsAgo) {
            $month = now()->subMonthsNoOverflow($monthsAgo);

            foreach ([$mobile, $card, $bank] as $index => $wallet) {
                $create->handle($company, TransactionType::Income, $wallet, random_int(800_000, 2_500_000), $month->copy()->startOfMonth()->addDays(3 + $index * 7), $commission, 'Monthly sales settlement', creator: $owner);
            }

            $create->handle($company, TransactionType::Income, $bank, random_int(100_000, 500_000), $month->copy()->startOfMonth()->addDays(10), $setupFee, 'New merchant setup fees', creator: $owner);

            $create->handle($company, TransactionType::Expense, $bank, 5_000_000, $month->copy()->startOfMonth()->addDays(5), $salaries, 'Monthly salaries', creator: $owner);
            $create->handle($company, TransactionType::Expense, $bank, 1_500_000, $month->copy()->startOfMonth()->addDays(2), $officeRent, 'Office rent', creator: $owner);
            $create->handle($company, TransactionType::Expense, $bank, random_int(300_000, 600_000), $month->copy()->startOfMonth()->addDays(7), $hosting, 'Servers and hosting', creator: $owner);
            $create->handle($company, TransactionType::Expense, $mobile, random_int(100_000, 400_000), $month->copy()->startOfMonth()->addDays(12), $marketing, 'Facebook ads', creator: $owner);
            $create->handle($company, TransactionType::Expense, $bank, random_int(20_000, 60_000), $month->copy()->endOfMonth()->subDays(2), $bankCharges, 'Bank charges', creator: $owner);
        }

        $create->handle($company, TransactionType::CapitalWithdrawal, $bank, 2_000_000, now()->subMonths(2)->startOfMonth()->addDays(20), description: 'Capital withdrawal', creator: $owner);
        $create->handle($company, TransactionType::CapitalWithdrawal, $mobile, 500_000, now()->subMonth()->startOfMonth()->addDays(25), description: 'Capital withdrawal', creator: $owner);

        $marketingBudget = 500_000;
        Budget::create(['company_id' => $company->id, 'category_id' => $marketing->id, 'amount' => $marketingBudget, 'alert_threshold' => 80]);
        Budget::create(['company_id' => $company->id, 'category_id' => $hosting->id, 'amount' => 800_000, 'alert_threshold' => 85]);

        $spentThisMonth = app(EvaluateBudgetAlert::class)->monthToDateSpend($company, $marketing, now());
        $topUp = (int) round($marketingBudget * 0.9) - $spentThisMonth;

        if ($topUp > 0) {
            $create->handle($company, TransactionType::Expense, $mobile, $topUp, now(), $marketing, 'Boosted campaign', creator: $owner);
        }

        foreach ([
            ['Office rent', TransactionType::Expense, $bank, $officeRent, 1_500_000],
            ['Monthly salaries', TransactionType::Expense, $bank, $salaries, 5_000_000],
            ['Hosting subscription', TransactionType::Expense, $bank, $hosting, 350_000],
        ] as [$name, $type, $wallet, $category, $amount]) {
            RecurringTransaction::create([
                'company_id' => $company->id,
                'name' => $name,
                'type' => $type,
                'wallet_id' => $wallet->id,
                'category_id' => $category->id,
                'amount' => $amount,
                'frequency' => RecurrenceFrequency::Monthly,
                'interval' => 1,
                'day_of_month' => 1,
                'starts_on' => now()->addMonthNoOverflow()->startOfMonth()->toDateString(),
                'next_run_on' => now()->addMonthNoOverflow()->startOfMonth()->toDateString(),
                'is_active' => true,
            ]);
        }

        if (Artisan::call('moneta:verify-balances') !== 0) {
            throw new RuntimeException('Demo seed produced an inconsistent ledger: '.Artisan::output());
        }

        $this->command->info('Demo data seeded. Login: demo@example.com / password');
    }

    private function wallet(Company $company, string $name): Wallet
    {
        return Wallet::query()->forCompany($company)->where('name', $name)->firstOrFail();
    }

    private function category(Company $company, string $name): Category
    {
        return Category::query()->forCompany($company)->where('name', $name)->firstOrFail();
    }
}
