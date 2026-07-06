<?php

declare(strict_types=1);

namespace Modules\Ledger\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Shared\Support\AmountRules;

/** I-2: transfer wajib dompet tujuan != asal, tanpa kategori. */
class StoreTransferRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        $activeOwnedWallet = fn () => Rule::exists('wallets', 'id')
            ->where('user_id', $userId)
            ->where('is_archived', false)
            ->whereNull('deleted_at');

        return [
            'wallet_id' => ['required', $activeOwnedWallet()],
            'destination_wallet_id' => ['required', 'different:wallet_id', $activeOwnedWallet()],
            'category_id' => ['prohibited'],
            'amount' => AmountRules::rules(),
            'description' => ['nullable', 'string', 'max:255'],
            'occurred_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now('Asia/Jakarta')->toDateString()],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'occurred_on.before_or_equal' => 'Tanggal tidak boleh di masa depan.',
            'destination_wallet_id.different' => 'Dompet tujuan harus berbeda dari dompet asal.',
        ];
    }
}
