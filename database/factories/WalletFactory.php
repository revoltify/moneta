<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WalletType;
use App\Models\Company;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
final class WalletFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->words(2, true),
            'type' => fake()->randomElement(WalletType::cases()),
            'currency' => 'BDT',
            'opening_balance' => 0,
            'cached_balance' => 0,
        ];
    }
}
