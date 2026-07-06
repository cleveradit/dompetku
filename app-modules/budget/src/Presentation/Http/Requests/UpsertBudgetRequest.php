<?php

declare(strict_types=1);

namespace Modules\Budget\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Shared\Support\AmountRules;

class UpsertBudgetRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // I-8 / AC-14.2: wajib kategori expense milik user.
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->where('type', 'expense')
                    ->whereNull('deleted_at'),
            ],
            'month' => ['required', 'date_format:Y-m-d'],
            'amount' => AmountRules::rules(),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'category_id.exists' => 'Anggaran hanya untuk kategori pengeluaran milikmu.',
        ];
    }
}
