<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Enums\WalletType;
use App\Http\Requests\Concerns\ResolvesCurrentCompany;
use App\Models\Wallet;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveWalletRequest extends FormRequest
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
        $wallet = $this->route('wallet');

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('wallets', 'name')
                    ->where('company_id', $this->company()->id)
                    ->ignore($wallet instanceof Wallet ? $wallet->id : null),
            ],
            'type' => ['required', Rule::enum(WalletType::class)],
            'account_number' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'opening_balance' => ['nullable', 'integer'],
            'currency' => ['nullable', Rule::in(Money::codes())],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('opening_balance') && is_numeric(str_replace([',', ' '], '', (string) $this->input('opening_balance')))) {
            $this->merge(['opening_balance' => Money::toMinorUnits((string) $this->input('opening_balance'))]);
        }
    }
}
