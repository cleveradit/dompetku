<?php

declare(strict_types=1);

namespace Modules\Ledger\Presentation\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ledger\Application\Actions\ImportTransactionsCsv;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * US-22: import transaksi dari CSV, halaman Pengaturan > Data.
 */
class ImportController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('settings/data', [
            'lastImportResult' => $request->session()->get('import_result'),
        ]);
    }

    /** AC-22.1: template CSV siap unduh, header + satu baris contoh. */
    public function template(): StreamedResponse
    {
        $rows = [
            ImportTransactionsCsv::HEADER,
            ['2026-07-01', 'pengeluaran', 'Makan & Minum', 'BCA', '25000.50', 'Makan siang'],
        ];

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'template-import-dompetku.csv', ['Content-Type' => 'text/csv']);
    }

    public function store(Request $request, ImportTransactionsCsv $importTransactionsCsv): RedirectResponse
    {
        // AC-22.3: bukan CSV atau > 2 MB ditolak sebelum diproses.
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ], [
            'file.mimes' => 'File harus berformat CSV.',
            'file.max' => 'Ukuran file maksimal 2 MB.',
        ]);

        $contents = file_get_contents($request->file('file')->getRealPath());

        $result = $importTransactionsCsv->handle((int) $request->user()->id, $contents === false ? '' : $contents);

        return back()->with('import_result', $result)->with('success', 'Import selesai.');
    }
}
