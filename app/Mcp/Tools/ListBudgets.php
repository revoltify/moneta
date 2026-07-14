<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Budgets\EvaluateBudgetAlert;
use App\Mcp\Concerns\InteractsWithCompany;
use App\Models\Budget;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
final class ListBudgets extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'List spending budgets with current period spend and usage percent. Amounts are integers in minor units (1/100 of the currency).';

    public function __construct(private EvaluateBudgetAlert $evaluator) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $company = $this->company($request);

        $budgets = Budget::query()
            ->forCompany($company)
            ->with('category')
            ->get()
            ->map(function (Budget $budget) use ($company) {
                $spent = $this->evaluator->periodSpend($company, $budget->category, now($company->timezone), $budget->period);

                return [
                    'id' => $budget->id,
                    'category' => $budget->category->name,
                    'period' => $budget->period,
                    'amount' => $budget->amount,
                    'spent' => $spent,
                    'usagePercent' => $budget->amount > 0 ? (int) round($spent * 100 / $budget->amount) : 0,
                    'alertThreshold' => $budget->alert_threshold,
                ];
            });

        return Response::json(['company' => $company->slug, 'budgets' => $budgets]);
    }
}
