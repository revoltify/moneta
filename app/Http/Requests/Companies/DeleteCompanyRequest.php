<?php

declare(strict_types=1);

namespace App\Http\Requests\Companies;

use App\Models\Company;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

final class DeleteCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('delete', $this->route('company'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('name') !== $this->company()->name) {
                    $validator->errors()->add('name', __('The company name does not match.'));
                }
            },
        ];
    }

    /**
     * Get the company associated with the request.
     */
    private function company(): Company
    {
        $company = $this->route('company');

        abort_if(! $company instanceof Company, 404);

        return $company;
    }
}
