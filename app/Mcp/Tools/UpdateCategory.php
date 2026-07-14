<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Categories\UpdateCategory as UpdateCategoryAction;
use App\Mcp\Concerns\InteractsWithCompany;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class UpdateCategory extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'Rename a category.';

    public function __construct(private UpdateCategoryAction $updateCategory) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'category' => $schema->string()->description('Category id or name.')->required(),
            'name' => $schema->string()->description('New name.')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate([
            'category' => 'required',
            'name' => 'required|string|max:100',
        ]);

        $company = $this->company($request);

        $category = $this->category($company, $request->get('category'));
        $previousName = $category->name;

        $this->updateCategory->handle($category, (string) $request->get('name'), $category->icon, $category->color);

        return Response::text(sprintf('Category "%s" renamed to "%s".', $previousName, $category->refresh()->name));
    }
}
