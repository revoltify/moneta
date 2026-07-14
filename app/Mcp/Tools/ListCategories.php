<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithCompany;
use App\Models\Category;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
final class ListCategories extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'List income and expense categories of a company, including parent/child structure.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'kind' => $schema->string()->enum(['income', 'expense'])->description('Filter by category kind.'),
            'include_archived' => $schema->boolean()->description('Include archived categories.')->default(false),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate(['kind' => 'nullable|in:income,expense']);

        $company = $this->company($request);

        $categories = $company->categories()
            ->when($request->get('kind'), fn ($query, $kind) => $query->where('kind', $kind))
            ->when($request->get('include_archived') !== true, fn ($query) => $query->active())
            ->orderBy('kind')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'kind' => $category->kind->value,
                'parentId' => $category->parent_id,
                'archived' => $category->isArchived(),
            ]);

        return Response::json(['company' => $company->slug, 'categories' => $categories]);
    }
}
