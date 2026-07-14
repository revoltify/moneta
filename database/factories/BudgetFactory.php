<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CategoryKind;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
final class BudgetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'category_id' => Category::factory()->kind(CategoryKind::Expense),
            'period' => 'monthly',
            'amount' => fake()->numberBetween(50_000, 5_000_000),
            'alert_threshold' => 80,
            'is_active' => true,
        ];
    }
}
