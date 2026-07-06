<?php

declare(strict_types=1);

namespace Modules\Ledger\Presentation\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ledger\Application\Actions\DeleteTransaction;
use Modules\Ledger\Application\Actions\RecordTransaction;
use Modules\Ledger\Application\Actions\UpdateTransaction;
use Modules\Ledger\Application\Queries\TransactionIndexQuery;
use Modules\Ledger\Domain\Enums\TransactionType;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Ledger\Presentation\Http\Requests\StoreTransactionRequest;
use Modules\Ledger\Presentation\Http\Requests\UpdateTransactionRequest;

class TransactionController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, TransactionIndexQuery $query): Response
    {
        return Inertia::render('transactions/index', $query->handle($request));
    }

    public function store(StoreTransactionRequest $request, RecordTransaction $recordTransaction): RedirectResponse
    {
        $validated = $request->validated();

        $recordTransaction->handle(
            userId: $request->user()->id,
            type: TransactionType::from($validated['type']),
            walletId: (int) $validated['wallet_id'],
            destinationWalletId: null,
            categoryId: (int) $validated['category_id'],
            amount: $validated['amount'],
            occurredOn: $validated['occurred_on'],
            description: $validated['description'] ?? null,
        );

        return back()->with('success', __('ui.transaction_saved'));
    }

    public function update(
        UpdateTransactionRequest $request,
        Transaction $transaction,
        UpdateTransaction $updateTransaction,
    ): RedirectResponse {
        $this->authorize('update', $transaction);

        $validated = $request->validated();
        $type = TransactionType::from($validated['type']);

        $updateTransaction->handle(
            transaction: $transaction,
            type: $type,
            walletId: (int) $validated['wallet_id'],
            destinationWalletId: $type === TransactionType::Transfer ? (int) $validated['destination_wallet_id'] : null,
            categoryId: $type === TransactionType::Transfer ? null : (int) $validated['category_id'],
            amount: $validated['amount'],
            occurredOn: $validated['occurred_on'],
            description: $validated['description'] ?? null,
        );

        return back()->with('success', __('ui.transaction_updated'));
    }

    public function destroy(Transaction $transaction, DeleteTransaction $deleteTransaction): RedirectResponse
    {
        $this->authorize('delete', $transaction);

        $deleteTransaction->handle($transaction);

        return back()->with('success', __('ui.transaction_deleted'));
    }
}
