<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Models\Category;

final class ArchiveCategory
{
    public function handle(Category $category): Category
    {
        $category->update(['archived_at' => $category->isArchived() ? null : now()]);

        return $category;
    }
}
