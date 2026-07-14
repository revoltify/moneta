<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Enums\RecurrenceFrequency;
use App\Enums\TransactionType;
use App\Http\Requests\Concerns\ResolvesCurrentCompany;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveRecurringTransactionRequest extends FormRequest
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
        $type = TransactionType::tryFrom((string) $this->input('type'));
        $company = $this->company();

        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::enum(TransactionType::class)->only([
                TransactionType::Income,
                TransactionType::Expense,
                TransactionType::Transfer,
            ])],
            'wallet_id' => [
                'required',
                Rule::exists('wallets', 'id')->where('company_id', $company->id)->whereNull('archived_at'),
            ],
            'counter_wallet_id' => [
                Rule::requiredIf($type === TransactionType::Transfer),
                'nullable',
                'different:wallet_id',
                Rule::exists('wallets', 'id')->where('company_id', $company->id)->whereNull('archived_at'),
            ],
            'category_id' => [
                Rule::requiredIf($type?->requiresCategory() ?? false),
                'nullable',
                Rule::exists('categories', 'id')
                    ->where('company_id', $company->id)
                    ->where('kind', $type?->categoryKind()?->value)
                    ->whereNull('archived_at'),
            ],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
            'frequency' => ['required', Rule::enum(RecurrenceFrequency::class)],
            'interval' => ['required', 'integer', 'min:1', 'max:12'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after:starts_on'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('amount') && is_numeric(str_replace([',', ' '], '', (string) $this->input('amount')))) {
            $this->merge(['amount' => Money::toMinorUnits((string) $this->input('amount'))]);
        }
    }
}
