<?php

declare(strict_types=1);

namespace Modules\Ledger\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Ledger\Domain\Enums\RecurringFrequency;
use Modules\Ledger\Domain\Enums\TransactionType;
use Modules\Shared\Support\AmountRules;

/** I-9: aturan I-1 dan I-2 berlaku identik untuk recurring. */
class StoreRecurringRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $userId = $this->user()?->id;
        $type = $this->input('type');

        $activeOwnedWallet = fn () => Rule::exists('wallets', 'id')
            ->where('user_id', $userId)
            ->where('is_archived', false)
            ->whereNull('deleted_at');

        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'wallet_id' => ['required', $activeOwnedWallet()],
            'destination_wallet_id' => [
                'prohibited_unless:type,transfer',
                'required_if:type,transfer',
                'different:wallet_id',
                'nullable',
                $activeOwnedWallet(),
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
            'frequency' => ['required', Rule::enum(RecurringFrequency::class)],
            'interval' => ['required', 'integer', 'min:1', 'max:365'],
            'next_run_on' => ['required', 'date_format:Y-m-d'],
            'end_on' => ['nullable', 'date_format:Y-m-d', 'after:next_run_on'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'destination_wallet_id.different' => 'Dompet tujuan harus berbeda dari dompet asal.',
            'category_id.exists' => 'Kategori tidak cocok dengan tipe transaksi.',
            'end_on.after' => 'Tanggal akhir harus setelah tanggal mulai.',
        ];
    }
}
