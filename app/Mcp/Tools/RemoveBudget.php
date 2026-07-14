<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\CategoryKind;
use App\Mcp\Concerns\InteractsWithCompany;
use App\Models\Budget;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
final class RemoveBudget extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'Remove a spending budget from a category. Transactions are not affected.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'category' => $schema->string()->description('Expense category id or name.')->required(),
            'period' => $schema->string()->enum(['monthly', 'quarterly', 'yearly'])->description('Only remove the budget for this period. Omit to remove all periods.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate([
            'category' => 'required',
            'period' => 'nullable|in:monthly,quarterly,yearly',
        ]);

        $company = $this->company($request);

        $category = $this->category($company, $request->get('category'), CategoryKind::Expense);

        $deleted = Budget::query()
            ->forCompany($company)
            ->where('category_id', $category->id)
            ->when($request->get('period'), fn ($query, $period) => $query->where('period', $period))
            ->delete();

        if ($deleted === 0) {
            return Response::error(sprintf('No budget found for "%s".', $category->name));
        }

        return Response::text(sprintf('Removed %d budget(s) for "%s".', $deleted, $category->name));
    }
}
