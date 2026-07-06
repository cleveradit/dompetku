<?php

declare(strict_types=1);

namespace Modules\Ledger\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Ledger\Domain\Enums\CategoryType;
use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Shared\Support\Icons;

class StoreCategoryRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Category|null $category */
        $category = $this->route('category');

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('categories')
                    ->where('user_id', $this->user()?->id)
                    ->where('type', $this->input('type'))
                    ->whereNull('deleted_at')
                    ->ignore($category?->id),
            ],
            'type' => ['required', Rule::enum(CategoryType::class)],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', Rule::in(Icons::WHITELIST)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.unique' => 'Kamu sudah punya kategori dengan nama ini pada tipe yang sama.',
        ];
    }
}
