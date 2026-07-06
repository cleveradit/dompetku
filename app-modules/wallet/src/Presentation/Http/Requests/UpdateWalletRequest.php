<?php

declare(strict_types=1);

namespace Modules\Wallet\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Shared\Support\Icons;
use Modules\Wallet\Domain\Enums\WalletType;
use Modules\Wallet\Infrastructure\Models\Wallet;

class UpdateWalletRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Wallet|null $wallet */
        $wallet = $this->route('wallet');

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('wallets')
                    ->where('user_id', $this->user()?->id)
                    ->whereNull('deleted_at')
                    ->ignore($wallet?->id),
            ],
            'type' => ['required', Rule::enum(WalletType::class)],
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
