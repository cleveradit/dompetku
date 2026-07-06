<?php

declare(strict_types=1);

namespace Modules\Reporting\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * US-19: dijalankan lewat chain() setelah TransactionsExport selesai
 * di-queue, menandai hasil export siap diunduh (AC-19.2).
 */
class MarkTransactionsExportReady implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $userId,
        private readonly string $path,
        private readonly string $name,
    ) {}

    public function handle(): void
    {
        Cache::put("transactions-export:{$this->userId}", [
            'status' => 'ready',
            'path' => $this->path,
            'name' => $this->name,
        ], now()->addDay());
    }
}
