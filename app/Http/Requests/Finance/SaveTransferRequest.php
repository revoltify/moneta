<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Http\Requests\Concerns\ResolvesCurrentCompany;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveTransferRequest extends FormRequest
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
        $company = $this->company();

        return [
            'wallet_id' => [
                'required',
                Rule::exists('wallets', 'id')->where('company_id', $company->id)->whereNull('archived_at'),
            ],
            'counter_wallet_id' => [
                'required',
                'different:wallet_id',
                Rule::exists('wallets', 'id')->where('company_id', $company->id)->whereNull('archived_at'),
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
