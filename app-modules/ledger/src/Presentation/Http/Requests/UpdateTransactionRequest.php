<?php

declare(strict_types=1);

namespace Modules\Ledger\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Ledger\Domain\Enums\TransactionType;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Shared\Support\AmountRules;

/**
 * Edit transaksi (AC-07.7): mendukung ganti tipe/dompet/nominal. Dompet yang
 * sudah terarsip tetap sah HANYA bila tidak berubah (histori lama); target
 * baru harus dompet aktif.
 */
class UpdateTransactionRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $userId = $this->user()?->id;
        $type = $this->input('type');
        /** @var Transaction|null $transaction */
        $transaction = $this->route('transaction');

        $walletRule = function (string $field) use ($userId, $transaction) {
            return Rule::exists('wallets', 'id')
                ->where('user_id', $userId)
                ->whereNull('deleted_at')
                ->where(function ($query) use ($field, $transaction) {
                    $query->where('is_archived', false);

                    $unchanged = $transaction?->{$field === 'wallet_id' ? 'wallet_id' : 'destination_wallet_id'};
                    if ($unchanged !== null && (int) $this->input($field) === $unchanged) {
                        $query->orWhere('id', $unchanged);
                    }
                });
        };

        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'wallet_id' => ['required', $walletRule('wallet_id')],
            'destination_wallet_id' => [
                'prohibited_unless:type,transfer',
                'required_if:type,transfer',
                'different:wallet_id',
                'nullable',
                $walletRule('destination_wallet_id'),
            ],
            'category_id' => [
                'prohibited_unless:type,income,expense',
                'required_if:type,income',
                'required_if:type,expense',
                'nullable',
                Rule::exists('categories', 'id')
                    ->where('user_id', $userId)
                    ->where('type', $type === 'transfer' ? null : $type)
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
            'destination_wallet_id.different' => 'Dompet tujuan harus berbeda dari dompet asal.',
            'category_id.exists' => 'Kategori tidak cocok dengan tipe transaksi.',
        ];
    }
}
