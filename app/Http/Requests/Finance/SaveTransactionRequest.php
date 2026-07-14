<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Enums\TransactionType;
use App\Http\Requests\Concerns\ResolvesCurrentCompany;
use App\Models\Transaction;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveTransactionRequest extends FormRequest
{
    use ResolvesCurrentCompany;

    public function authorize(): bool
    {
        $type = TransactionType::tryFrom((string) $this->input('type'));

        if ($type === null) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = TransactionType::tryFrom((string) $this->input('type'));
        $company = $this->company();
        $transaction = $this->route('transaction');

        return [
            'type' => $transaction instanceof Transaction
                ? ['required', Rule::in([$transaction->type->value])]
                : ['required', Rule::enum(TransactionType::class)->except(TransactionType::Transfer)],
            'wallet_id' => [
                'required',
                Rule::exists('wallets', 'id')->where('company_id', $company->id)->whereNull('archived_at'),
            ],
            'category_id' => [
                Rule::requiredIf($type?->requiresCategory() ?? false),
                Rule::prohibitedIf(! ($type?->requiresCategory() ?? true)),
                'nullable',
                Rule::exists('categories', 'id')
                    ->where('company_id', $company->id)
                    ->where('kind', $type?->categoryKind()?->value)
                    ->whereNull('archived_at'),
            ],
            'amount' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('amount') && is_numeric(str_replace([',', ' '], '', (string) $this->input('amount')))) {
            $this->merge(['amount' => Money::toMinorUnits((string) $this->input('amount'))]);
        }
    }
}
