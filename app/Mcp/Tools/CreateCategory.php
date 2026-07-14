<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Categories\CreateCategory as CreateCategoryAction;
use App\Enums\CategoryKind;
use App\Mcp\Concerns\InteractsWithCompany;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class CreateCategory extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'Create an income or expense category, optionally under a parent category of the same kind.';

    public function __construct(private CreateCategoryAction $createCategory) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'name' => $schema->string()->required(),
            'kind' => $schema->string()->enum(['income', 'expense'])->required(),
            'parent' => $schema->string()->description('Parent category id or name (same kind).'),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'kind' => 'required|in:income,expense',
        ]);

        $company = $this->company($request);

        $kind = CategoryKind::from((string) $request->get('kind'));
        $parent = $request->get('parent') !== null
            ? $this->category($company, $request->get('parent'), $kind)
            : null;

        try {
            $category = $this->createCategory->handle($company, (string) $request->get('name'), $kind, $parent);
        } catch (InvalidArgumentException $exception) {
            return Response::error($exception->getMessage());
        }

        return Response::text(sprintf(
            'Category "%s" (#%d, %s%s) created.',
            $category->name,
            $category->id,
            $kind->value,
            $parent !== null ? ', under "'.$parent->name.'"' : '',
        ));
    }
}
