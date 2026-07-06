<?php

declare(strict_types=1);

namespace Modules\Ledger\Presentation\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Modules\Ledger\Application\Actions\TransferBetweenWallets;
use Modules\Ledger\Presentation\Http\Requests\StoreTransferRequest;

class TransferController extends Controller
{
    public function store(StoreTransferRequest $request, TransferBetweenWallets $transfer): RedirectResponse
    {
        $validated = $request->validated();

        $transfer->handle(
            userId: $request->user()->id,
            sourceWalletId: (int) $validated['wallet_id'],
            destinationWalletId: (int) $validated['destination_wallet_id'],
            amount: $validated['amount'],
            occurredOn: $validated['occurred_on'],
            description: $validated['description'] ?? null,
        );

        return back()->with('success', __('ui.transfer_saved'));
    }
}
