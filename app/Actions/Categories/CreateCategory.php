<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Enums\CategoryKind;
use App\Models\Category;
use App\Models\Company;
use InvalidArgumentException;

final class CreateCategory
{
    public function handle(
        Company $company,
        string $name,
        CategoryKind $kind,
        ?Category $parent = null,
        ?string $icon = null,
        ?string $color = null,
    ): Category {
        if ($parent !== null) {
            $this->assertValidParent($company, $parent, $kind);
        }

        return Category::create([
            'company_id' => $company->id,
            'parent_id' => $parent?->id,
            'kind' => $kind,
            'name' => $name,
            'icon' => $icon,
            'color' => $color,
        ]);
    }

    private function assertValidParent(Company $company, Category $parent, CategoryKind $kind): void
    {
        if ($parent->company_id !== $company->id) {
            throw new InvalidArgumentException('Parent category belongs to another company.');
        }

        if ($parent->kind !== $kind) {
            throw new InvalidArgumentException('Parent category kind does not match.');
        }

        if ($parent->parent_id !== null) {
            throw new InvalidArgumentException('Categories can only be nested one level deep.');
        }
    }
}
