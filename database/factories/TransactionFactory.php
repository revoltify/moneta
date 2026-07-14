<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CategoryKind;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
final class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => TransactionType::Expense,
            'wallet_id' => Wallet::factory(),
            'category_id' => Category::factory()->kind(CategoryKind::Expense),
            'amount' => fake()->numberBetween(1_000, 500_000),
            'currency' => 'BDT',
            'date' => fake()->dateTimeBetween('-3 months')->format('Y-m-d'),
            'status' => 'posted',
        ];
    }
}
