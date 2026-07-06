<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Shared\Support\Currencies;

class ProfileUpdateRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique('users')->ignore($this->user()?->id),
            ],
            'currency' => ['sometimes', 'required', 'string', Rule::in(Currencies::SUPPORTED)],
        ];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return array_merge($this->all(), [
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
        ]);
    }
}
