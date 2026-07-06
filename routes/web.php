<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Budget\Presentation\Http\Controllers\BudgetController;
use Modules\Ledger\Presentation\Http\Controllers\AttachmentController;
use Modules\Ledger\Presentation\Http\Controllers\CategoryController;
use Modules\Ledger\Presentation\Http\Controllers\ImportController;
use Modules\Ledger\Presentation\Http\Controllers\RecurringController;
use Modules\Ledger\Presentation\Http\Controllers\TransactionController;
use Modules\Ledger\Presentation\Http\Controllers\TransferController;
use Modules\Reporting\Presentation\Http\Controllers\DashboardController;
use Modules\Reporting\Presentation\Http\Controllers\ExportController;
use Modules\Reporting\Presentation\Http\Controllers\ReportController;
use Modules\Wallet\Presentation\Http\Controllers\WalletController;

Route::get('/', fn () => redirect()->route('dashboard'))->name('home');

Route::get('/sampai-jumpa', fn () => Inertia::render('goodbye'))->name('goodbye');

// US-23: halaman offline yang ditampilkan service worker saat navigasi gagal.
Route::get('/offline', fn () => Inertia::render('offline'))->name('offline');

Route::middleware(['auth', 'verified'])->group(function () {
    // Onboarding dompet pertama (di luar guard has.wallet).
    Route::get('wallets/first', [WalletController::class, 'first'])->name('wallets.first');
    Route::post('wallets', [WalletController::class, 'store'])->name('wallets.store');

    Route::middleware('has.wallet')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

        Route::get('wallets', [WalletController::class, 'index'])->name('wallets.index');
        Route::patch('wallets/{wallet}', [WalletController::class, 'update'])->name('wallets.update');
        Route::post('wallets/{wallet}/archive', [WalletController::class, 'archive'])->name('wallets.archive');
        Route::delete('wallets/{wallet}', [WalletController::class, 'destroy'])->name('wallets.destroy');

        Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
        Route::patch('transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

        Route::post('transfers', [TransferController::class, 'store'])->name('transfers.store');

        // US-18: lampiran struk (Fase 2).
        Route::post('transactions/{transaction}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
        Route::get('attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');
        Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

        // US-19: export CSV/Excel (Fase 2). GET agar browser bisa mengunduh langsung.
        Route::get('exports/transactions', [ExportController::class, 'transactions'])->name('exports.transactions');
        Route::get('exports/download', [ExportController::class, 'download'])->name('exports.download');

        // US-22: import CSV (Fase 3), menu Pengaturan > Data.
        Route::get('settings/data', [ImportController::class, 'show'])->name('data.edit');
        Route::get('imports/template', [ImportController::class, 'template'])->name('imports.template');
        Route::post('imports/transactions', [ImportController::class, 'store'])->name('imports.store');

        Route::get('recurring', [RecurringController::class, 'index'])->name('recurring.index');
        Route::post('recurring', [RecurringController::class, 'store'])->name('recurring.store');
        Route::patch('recurring/{recurring}', [RecurringController::class, 'update'])->name('recurring.update');
        Route::post('recurring/{recurring}/toggle', [RecurringController::class, 'toggle'])->name('recurring.toggle');
        Route::delete('recurring/{recurring}', [RecurringController::class, 'destroy'])->name('recurring.destroy');

        Route::get('budgets', [BudgetController::class, 'index'])->name('budgets.index');
        Route::post('budgets', [BudgetController::class, 'store'])->name('budgets.store');
        Route::post('budgets/copy', [BudgetController::class, 'copy'])->name('budgets.copy');
        Route::delete('budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');

        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::patch('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    });
});

require __DIR__.'/settings.php';
