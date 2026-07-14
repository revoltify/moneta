<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Enums\CategoryKind;
use App\Http\Requests\Concerns\ResolvesCurrentCompany;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveCategoryRequest extends FormRequest
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
        $category = $this->route('category');

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('categories', 'name')
                    ->where('company_id', $this->company()->id)
                    ->where('parent_id', $this->input('parent_id'))
                    ->where('kind', $this->input('kind'))
                    ->ignore($category instanceof Category ? $category->id : null),
            ],
            'kind' => ['required', Rule::enum(CategoryKind::class)],
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id')
                    ->where('company_id', $this->company()->id)
                    ->where('kind', $this->input('kind'))
                    ->whereNull('parent_id'),
            ],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
        ];
    }
}
