<?php

declare(strict_types=1);

namespace Modules\Reporting\Presentation\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use Modules\Ledger\Application\Queries\TransactionIndexQuery;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Reporting\Application\Exports\TransactionsExport;
use Modules\Reporting\Application\Jobs\MarkTransactionsExportReady;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * US-19: export transaksi terfilter ke CSV/Excel. Di bawah ambang baris
 * diunduh langsung; di atasnya diproses lewat queue (04-NFR.md P-4).
 */
class ExportController extends Controller
{
    public const QUEUE_THRESHOLD = 10000;

    public function transactions(Request $request, TransactionIndexQuery $transactionIndexQuery): RedirectResponse|BinaryFileResponse
    {
        $validated = $request->validate([
            'format' => ['nullable', 'in:csv,xlsx'],
        ]);

        $format = $validated['format'] ?? 'csv';
        $userId = (int) $request->user()->id;
        $filters = $transactionIndexQuery->filters($request);

        $count = $transactionIndexQuery->applyFilters(Transaction::query(), $filters)->count();

        $extension = $format === 'xlsx' ? 'xlsx' : 'csv';
        $writerType = $format === 'xlsx' ? Excel::XLSX : Excel::CSV;
        $fileName = 'transaksi-'.now()->format('YmdHis').'.'.$extension;

        if ($count <= self::QUEUE_THRESHOLD) {
            return ExcelFacade::download(new TransactionsExport($userId, $filters), $fileName, $writerType);
        }

        $path = "exports/{$userId}/".Str::uuid()->toString().'.'.$extension;

        Cache::put("transactions-export:{$userId}", ['status' => 'processing'], now()->addDay());

        (new TransactionsExport($userId, $filters))
            ->queue($path, 'local')
            ->chain([new MarkTransactionsExportReady($userId, $path, $fileName)]);

        return back()->with('success', __('ui.export_processing'));
    }

    public function download(Request $request): StreamedResponse
    {
        $userId = (int) $request->user()->id;
        $entry = Cache::get("transactions-export:{$userId}");

        abort_if($entry === null || ($entry['status'] ?? null) !== 'ready', 404);

        return Storage::disk('local')->download($entry['path'], $entry['name']);
    }
}
