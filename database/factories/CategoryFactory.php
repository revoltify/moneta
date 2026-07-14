<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CategoryKind;
use App\Models\Category;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
final class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'kind' => fake()->randomElement(CategoryKind::cases()),
            'name' => fake()->unique()->words(2, true),
        ];
    }

    public function kind(CategoryKind $kind): static
    {
        return $this->state(fn () => ['kind' => $kind]);
    }
}
