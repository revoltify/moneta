<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Actions\Budgets\EvaluateBudgetAlert;
use App\Enums\CategoryKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\SaveBudgetRequest;
use App\Models\Budget;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BudgetController extends Controller
{
    public function index(Request $request, Company $current_company, EvaluateBudgetAlert $evaluator): Response
    {
        $budgets = Budget::query()
            ->forCompany($current_company)
            ->with('category')
            ->get()
            ->map(fn (Budget $budget) => [
                'id' => $budget->id,
                'categoryId' => $budget->category_id,
                'categoryName' => $budget->category->name,
                'categoryColor' => $budget->category->color,
                'amount' => $budget->amount,
                'alertThreshold' => $budget->alert_threshold,
                'period' => $budget->period,
                'spent' => $evaluator->periodSpend($current_company, $budget->category, now($current_company->timezone), $budget->period),
            ])
            ->sortByDesc(fn (array $budget) => $budget['amount'] > 0 ? $budget['spent'] / $budget['amount'] : 0)
            ->values();

        return Inertia::render('budgets/index', [
            'budgets' => $budgets,
            'categories' => $current_company->categories()->active()
                ->where('kind', CategoryKind::Expense)
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($category) => ['id' => $category->id, 'name' => $category->name]),
        ]);
    }

    public function store(SaveBudgetRequest $request, Company $current_company): RedirectResponse
    {
        Budget::updateOrCreate(
            [
                'company_id' => $current_company->id,
                'category_id' => $request->validated('category_id'),
                'period' => $request->validated('period'),
            ],
            [
                'amount' => $request->validated('amount'),
                'alert_threshold' => $request->validated('alert_threshold'),
                'is_active' => true,
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Budget saved.')]);

        return back();
    }

    public function destroy(Request $request, Company $current_company, Budget $budget): RedirectResponse
    {

        $budget->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Budget removed.')]);

        return back();
    }
}
