<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\CategoryKind;
use App\Mcp\Concerns\InteractsWithCompany;
use App\Models\Budget;
use App\Support\Money;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class SetBudget extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'Create or update a spending budget for a top-level expense category.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'category' => $schema->string()->description('Expense category id or name (top-level).')->required(),
            'amount' => $schema->string()->description('Decimal budget amount in the company currency.')->required(),
            'period' => $schema->string()->enum(['monthly', 'quarterly', 'yearly'])->default('monthly'),
            'alert_threshold' => $schema->integer()->description('Alert when spend reaches this percent.')->default(80),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate([
            'category' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'period' => 'nullable|in:monthly,quarterly,yearly',
            'alert_threshold' => 'nullable|integer|min:1|max:100',
        ]);

        $company = $this->company($request);

        $category = $this->category($company, $request->get('category'), CategoryKind::Expense);

        if ($category->parent_id !== null) {
            return Response::error('Budgets can only be set on top-level expense categories. Child spend counts toward the parent budget.');
        }

        $period = (string) $request->get('period', 'monthly');

        $budget = Budget::updateOrCreate(
            [
                'company_id' => $company->id,
                'category_id' => $category->id,
                'period' => $period,
            ],
            [
                'amount' => Money::toMinorUnits((string) $request->get('amount')),
                'alert_threshold' => (int) $request->get('alert_threshold', 80),
                'is_active' => true,
            ],
        );

        return Response::text(sprintf(
            '%s budget of %s set for "%s" (alerts at %d%%).',
            ucfirst($period),
            Money::format($budget->amount, $company->currency),
            $category->name,
            $budget->alert_threshold,
        ));
    }
}
