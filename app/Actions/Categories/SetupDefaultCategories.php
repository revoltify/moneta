<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Enums\CategoryKind;
use App\Models\Company;

final class SetupDefaultCategories
{
    private const array DEFAULTS = [
        CategoryKind::Income->value => [
            ['Sales', 'shopping-cart', '#16a34a'],
            ['Services', 'briefcase', '#0d9488'],
            ['Other Income', 'circle-plus', '#64748b'],
        ],
        CategoryKind::Expense->value => [
            ['Software & Hosting', 'server', '#dc2626'],
            ['Marketing', 'megaphone', '#ea580c'],
            ['Salaries', 'users', '#7c3aed'],
            ['Office Rent', 'building', '#2563eb'],
            ['Bank Charges', 'landmark', '#475569'],
            ['Other Expense', 'circle-minus', '#64748b'],
        ],
    ];

    public function __construct(private CreateCategory $createCategory) {}

    public function handle(Company $company): void
    {
        foreach (self::DEFAULTS as $kind => $categories) {
            foreach ($categories as [$name, $icon, $color]) {
                $this->createCategory->handle($company, $name, CategoryKind::from($kind), icon: $icon, color: $color);
            }
        }
    }
}
