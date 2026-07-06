<?php

declare(strict_types=1);

namespace Modules\Ledger\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Ledger\Domain\Enums\TransactionType;
use Modules\Shared\Support\AmountRules;

/**
 * Aturan I-1..I-5 (02-DATABASE.md §3) + aturan validasi 04-NFR.md §2 untuk
 * income/expense. Transfer memakai StoreTransferRequest.
 */
class StoreTransactionRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $userId = $this->user()?->id;
        $type = $this->input('type');

        return [
            'type' => ['required', Rule::in([TransactionType::Income->value, TransactionType::Expense->value])],
            'wallet_id' => [
                'required',
                Rule::exists('wallets', 'id')
                    ->where('user_id', $userId)
                    ->where('is_archived', false)
                    ->whereNull('deleted_at'),
            ],
            'destination_wallet_id' => ['prohibited'],
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')
                    ->where('user_id', $userId)
                    ->where('type', $type)
                    ->whereNull('deleted_at'),
            ],
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
            'wallet_id.exists' => 'Dompet tidak tersedia untuk transaksi baru.',
            'category_id.exists' => 'Kategori tidak cocok dengan tipe transaksi.',
        ];
    }
}
