<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Enums\CategoryKind;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Company;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class IncomeStatementReport
{
    /**
     * @return array{
     *     income: list<array{id: int, name: string, color: string|null, amount: int, children: list<array{id: int, name: string, color: string|null, amount: int}>}>,
     *     expense: list<array{id: int, name: string, color: string|null, amount: int, children: list<array{id: int, name: string, color: string|null, amount: int}>}>,
     *     totalIncome: int,
     *     totalExpense: int,
     *     netProfit: int
     * }
     */
    public function generate(Company $company, CarbonInterface $from, CarbonInterface $to): array
    {
        $totals = DB::table('transactions')
            ->where('company_id', $company->id)
            ->where('status', TransactionStatus::Posted->value)
            ->where('currency', $company->currency)
            ->whereIn('type', [TransactionType::Income->value, TransactionType::Expense->value])
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->groupBy('category_id')
            ->selectRaw('category_id, COALESCE(SUM(amount), 0) as total')
            ->pluck('total', 'category_id');

        $categories = Category::query()->forCompany($company)->get();
        $amountsByCategory = $categories->mapWithKeys(
            fn (Category $category) => [$category->id => (int) ($totals[$category->id] ?? 0)],
        );

        $sections = [];

        foreach (CategoryKind::cases() as $kind) {
            $parents = $categories->where('kind', $kind)->whereNull('parent_id');
            $rows = [];

            foreach ($parents as $parent) {
                $children = [];

                foreach ($categories->where('parent_id', $parent->id) as $child) {
                    $childAmount = $amountsByCategory[$child->id];

                    if ($childAmount === 0) {
                        continue;
                    }

                    $children[] = [
                        'id' => $child->id,
                        'name' => $child->name,
                        'color' => $child->color,
                        'amount' => $childAmount,
                    ];
                }

                $amount = $amountsByCategory[$parent->id] + array_sum(array_column($children, 'amount'));

                if ($amount === 0 && $children === []) {
                    continue;
                }

                $rows[] = [
                    'id' => $parent->id,
                    'name' => $parent->name,
                    'color' => $parent->color,
                    'amount' => $amount,
                    'children' => $children,
                ];
            }

            usort($rows, fn (array $a, array $b) => $b['amount'] <=> $a['amount']);

            $sections[$kind->value] = $rows;
        }

        $totalIncome = array_sum(array_column($sections['income'], 'amount'));
        $totalExpense = array_sum(array_column($sections['expense'], 'amount'));

        return [
            'income' => $sections['income'],
            'expense' => $sections['expense'],
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netProfit' => $totalIncome - $totalExpense,
        ];
    }
}
