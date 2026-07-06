<?php

declare(strict_types=1);

namespace Modules\Wallet\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Shared\Support\AmountRules;
use Modules\Shared\Support\Icons;
use Modules\Wallet\Domain\Enums\WalletType;

class StoreWalletRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('wallets')
                    ->where('user_id', $this->user()?->id)
                    ->whereNull('deleted_at'),
            ],
            'type' => ['required', Rule::enum(WalletType::class)],
            'initial_balance' => AmountRules::rules(min: '0'),
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', Rule::in(Icons::WHITELIST)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.unique' => 'Kamu sudah punya dompet dengan nama ini.',
        ];
    }
}
