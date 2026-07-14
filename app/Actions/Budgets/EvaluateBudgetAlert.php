<?php

declare(strict_types=1);

namespace App\Actions\Budgets;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Company;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class EvaluateBudgetAlert
{
    /**
     * @return array{message: string, level: string}|null
     */
    public function handle(Company $company, Category $category, CarbonInterface $date): ?array
    {
        $budgetCategory = $category->parent_id !== null ? $category->parent : $category;

        if ($budgetCategory === null) {
            return null;
        }

        $budgets = Budget::query()
            ->forCompany($company)
            ->where('category_id', $budgetCategory->id)
            ->where('is_active', true)
            ->get();

        foreach ($budgets as $budget) {
            $alert = $this->evaluate($company, $budgetCategory, $budget, $date);

            if ($alert !== null) {
                return $alert;
            }
        }

        return null;
    }

    public function periodSpend(Company $company, Category $category, CarbonInterface $date, string $period = 'monthly'): int
    {
        [$from, $to] = $this->periodWindow($date, $period);

        $categoryIds = [$category->id, ...$category->children()->pluck('id')->all()];

        return (int) DB::table('transactions')
            ->where('company_id', $company->id)
            ->where('status', TransactionStatus::Posted->value)
            ->where('type', TransactionType::Expense->value)
            ->where('currency', $company->currency)
            ->whereIn('category_id', $categoryIds)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->sum('amount');
    }

    public function monthToDateSpend(Company $company, Category $category, CarbonInterface $date): int
    {
        return $this->periodSpend($company, $category, $date, 'monthly');
    }

    /**
     * @return array{message: string, level: string}|null
     */
    private function evaluate(Company $company, Category $category, Budget $budget, CarbonInterface $date): ?array
    {
        $spent = $this->periodSpend($company, $category, $date, $budget->period);
        $percent = (int) floor($spent * 100 / max(1, $budget->amount));
        $periodLabel = ucfirst($budget->period);

        if ($percent >= 100) {
            return [
                'message' => __(':category :period budget exceeded: :spent of :budget (:percent%)', [
                    'category' => $category->name,
                    'period' => mb_strtolower($periodLabel),
                    'spent' => Money::format($spent),
                    'budget' => Money::format($budget->amount),
                    'percent' => $percent,
                ]),
                'level' => 'error',
            ];
        }

        if ($percent >= $budget->alert_threshold) {
            return [
                'message' => __(':category :period budget at :percent% (:spent of :budget)', [
                    'category' => $category->name,
                    'period' => mb_strtolower($periodLabel),
                    'percent' => $percent,
                    'spent' => Money::format($spent),
                    'budget' => Money::format($budget->amount),
                ]),
                'level' => 'warning',
            ];
        }

        return null;
    }

    /**
     * @return array{string, string}
     */
    private function periodWindow(CarbonInterface $date, string $period): array
    {
        $date = Carbon::parse($date);

        return match ($period) {
            'quarterly' => [$date->copy()->firstOfQuarter()->toDateString(), $date->copy()->lastOfQuarter()->toDateString()],
            'yearly' => [$date->copy()->startOfYear()->toDateString(), $date->copy()->endOfYear()->toDateString()],
            default => [$date->copy()->startOfMonth()->toDateString(), $date->copy()->endOfMonth()->toDateString()],
        };
    }
}
