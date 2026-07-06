<?php

declare(strict_types=1);

namespace Modules\Ledger\Application\Actions;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Modules\Ledger\Domain\Enums\TransactionType;
use Modules\Ledger\Domain\Exceptions\ImportRowException;
use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Wallet\Infrastructure\Models\Wallet;
use Throwable;

/**
 * Import transaksi income/expense dari CSV sesuai template (US-22). Transfer
 * tidak didukung. Setiap baris divalidasi & disimpan secara independen
 * (AC-22.2: per baris atomic — satu baris gagal tidak membatalkan baris lain).
 */
class ImportTransactionsCsv
{
    public const HEADER = ['tanggal', 'tipe', 'kategori', 'dompet', 'nominal', 'catatan'];

    public function __construct(private readonly RecordTransaction $recordTransaction) {}

    /** @return array{imported: int, failed: list<array{line: int, reason: string}>} */
    public function handle(int $userId, string $csvContents): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $csvContents) ?: [];

        $imported = 0;
        $failed = [];
        $lineNumber = 0;
        $headerChecked = false;

        foreach ($lines as $rawLine) {
            $lineNumber++;

            if (trim($rawLine) === '') {
                continue;
            }

            $row = str_getcsv($rawLine);

            if (! $headerChecked) {
                $headerChecked = true;
                $normalizedHeader = array_map(static fn (?string $value): string => strtolower(trim((string) $value)), $row);
                if ($normalizedHeader === self::HEADER) {
                    continue;
                }
                // Bukan header yang dikenali: perlakukan sebagai baris data biasa.
            }

            try {
                $this->importRow($userId, $row);
                $imported++;
            } catch (ImportRowException $exception) {
                $failed[] = ['line' => $lineNumber, 'reason' => $exception->getMessage()];
            }
        }

        return ['imported' => $imported, 'failed' => $failed];
    }

    /** @param  list<string|null>  $row */
    private function importRow(int $userId, array $row): void
    {
        if (count($row) < 6) {
            throw new ImportRowException('Jumlah kolom tidak sesuai template.');
        }

        [$tanggal, $tipe, $kategoriName, $dompetName, $nominal, $catatan] = array_map(
            static fn (?string $value): string => trim((string) $value),
            array_slice($row, 0, 6),
        );

        $type = match (strtolower($tipe)) {
            'pemasukan' => TransactionType::Income,
            'pengeluaran' => TransactionType::Expense,
            default => throw new ImportRowException("Tipe '{$tipe}' tidak dikenali (harus 'pemasukan' atau 'pengeluaran')."),
        };

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $nominal) || (float) $nominal <= 0) {
            throw new ImportRowException("Nominal '{$nominal}' tidak valid.");
        }

        $today = CarbonImmutable::now('Asia/Jakarta')->toDateString();
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            throw new ImportRowException("Tanggal '{$tanggal}' tidak sesuai format YYYY-MM-DD.");
        }
        if ($tanggal > $today) {
            throw new ImportRowException('Tanggal di masa depan.');
        }

        $categoryType = $type === TransactionType::Income ? 'income' : 'expense';
        $category = Category::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->where('type', $categoryType)
            ->whereRaw('LOWER(name) = ?', [strtolower($kategoriName)])
            ->first();

        if ($category === null) {
            throw new ImportRowException("Kategori '{$kategoriName}' tidak ditemukan.");
        }

        $wallet = Wallet::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->where('is_archived', false)
            ->whereRaw('LOWER(name) = ?', [strtolower($dompetName)])
            ->first();

        if ($wallet === null) {
            throw new ImportRowException("Dompet '{$dompetName}' tidak ditemukan atau sudah diarsipkan.");
        }

        try {
            $this->recordTransaction->handle(
                userId: $userId,
                type: $type,
                walletId: $wallet->id,
                destinationWalletId: null,
                categoryId: $category->id,
                amount: (string) BigDecimal::of($nominal)->toScale(2, RoundingMode::UNNECESSARY),
                occurredOn: $tanggal,
                description: $catatan !== '' ? $catatan : null,
            );
        } catch (Throwable $exception) {
            throw new ImportRowException('Gagal menyimpan transaksi: '.$exception->getMessage());
        }
    }
}
