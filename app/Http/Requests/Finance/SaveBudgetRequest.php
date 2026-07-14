<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Enums\CategoryKind;
use App\Http\Requests\Concerns\ResolvesCurrentCompany;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveBudgetRequest extends FormRequest
{
    use ResolvesCurrentCompany;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')
                    ->where('company_id', $this->company()->id)
                    ->where('kind', CategoryKind::Expense->value)
                    ->whereNull('parent_id')
                    ->whereNull('archived_at'),
            ],
            'period' => ['required', Rule::in(['monthly', 'quarterly', 'yearly'])],
            'amount' => ['required', 'integer', 'min:1'],
            'alert_threshold' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('amount') && is_numeric(str_replace([',', ' '], '', (string) $this->input('amount')))) {
            $this->merge(['amount' => Money::toMinorUnits((string) $this->input('amount'))]);
        }
    }
}
