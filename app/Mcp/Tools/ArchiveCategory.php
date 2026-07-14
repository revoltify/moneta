<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Categories\ArchiveCategory as ArchiveCategoryAction;
use App\Mcp\Concerns\InteractsWithCompany;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class ArchiveCategory extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'Archive a category, or restore it if already archived. Archived categories are hidden from entry forms but keep their history.';

    public function __construct(private ArchiveCategoryAction $archiveCategory) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'category' => $schema->string()->description('Category id or name.')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate(['category' => 'required']);

        $company = $this->company($request);

        $category = $this->category($company, $request->get('category'));
        $this->archiveCategory->handle($category);

        return Response::text(sprintf(
            'Category "%s" %s.',
            $category->name,
            $category->refresh()->isArchived() ? 'archived' : 'restored',
        ));
    }
}
