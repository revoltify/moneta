<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Actions\Categories\ArchiveCategory;
use App\Actions\Categories\CreateCategory;
use App\Actions\Categories\DeleteCategory;
use App\Actions\Categories\UpdateCategory;
use App\Enums\CategoryKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\SaveCategoryRequest;
use App\Models\Category;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class CategoryController extends Controller
{
    public function index(Request $request, Company $current_company): Response
    {
        $categories = $current_company->categories()
            ->withExists('children')
            ->orderBy('name')
            ->get();

        $categoriesWithActivity = DB::table('transactions')
            ->where('company_id', $current_company->id)
            ->whereIn('category_id', $categories->pluck('id'))
            ->distinct()
            ->pluck('category_id')
            ->flip();

        $hasActivity = $categories->mapWithKeys(
            fn (Category $category) => [$category->id => $categoriesWithActivity->has($category->id)],
        );

        $payload = $categories->map(fn (Category $category) => [
            'id' => $category->id,
            'parentId' => $category->parent_id,
            'kind' => $category->kind->value,
            'name' => $category->name,
            'icon' => $category->icon,
            'color' => $category->color,
            'archived' => $category->isArchived(),
            'hasActivity' => $hasActivity[$category->id],
            'hasChildren' => (bool) $category->getAttribute('children_exists'),
        ]);

        return Inertia::render('categories/index', [
            'categories' => $payload,
        ]);
    }

    public function store(SaveCategoryRequest $request, Company $current_company, CreateCategory $createCategory): RedirectResponse
    {
        $parent = $request->validated('parent_id')
            ? Category::query()->forCompany($current_company)->whereKey($request->validated('parent_id'))->firstOrFail()
            : null;

        $createCategory->handle(
            $current_company,
            $request->validated('name'),
            CategoryKind::from($request->validated('kind')),
            $parent,
            $request->validated('icon'),
            $request->validated('color'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        return back();
    }

    public function update(SaveCategoryRequest $request, Company $current_company, Category $category, UpdateCategory $updateCategory): RedirectResponse
    {

        $updateCategory->handle(
            $category,
            $request->validated('name'),
            $request->validated('icon'),
            $request->validated('color'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return back();
    }

    public function archive(Request $request, Company $current_company, Category $category, ArchiveCategory $archiveCategory): RedirectResponse
    {

        $archiveCategory->handle($category);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $category->isArchived() ? __('Category archived.') : __('Category restored.'),
        ]);

        return back();
    }

    public function destroy(Request $request, Company $current_company, Category $category, DeleteCategory $deleteCategory): RedirectResponse
    {

        try {
            $deleteCategory->handle($category);
        } catch (RuntimeException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category deleted.')]);

        return back();
    }
}
