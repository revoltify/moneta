<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CategoryKind;
use App\Enums\RecurrenceFrequency;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Company;
use App\Models\RecurringTransaction;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringTransaction>
 */
final class RecurringTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->words(2, true),
            'type' => TransactionType::Expense,
            'wallet_id' => Wallet::factory(),
            'category_id' => Category::factory()->kind(CategoryKind::Expense),
            'amount' => fake()->numberBetween(10_000, 500_000),
            'frequency' => RecurrenceFrequency::Monthly,
            'interval' => 1,
            'starts_on' => today()->toDateString(),
            'next_run_on' => today()->toDateString(),
            'is_active' => true,
        ];
    }
}
