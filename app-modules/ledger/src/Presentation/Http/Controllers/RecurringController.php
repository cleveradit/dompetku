<?php

declare(strict_types=1);

namespace Modules\Ledger\Presentation\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ledger\Application\Actions\CreateRecurring;
use Modules\Ledger\Application\Actions\ToggleRecurring;
use Modules\Ledger\Application\Actions\UpdateRecurring;
use Modules\Ledger\Domain\Enums\RecurringFrequency;
use Modules\Ledger\Domain\Enums\TransactionType;
use Modules\Ledger\Infrastructure\Models\RecurringTransaction;
use Modules\Ledger\Presentation\Http\Requests\StoreRecurringRequest;
use Modules\Wallet\Application\Queries\WalletOptionsQuery;

class RecurringController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, WalletOptionsQuery $walletOptions): Response
    {
        $walletMap = collect($walletOptions->all((int) $request->user()->id))->keyBy('id');

        $recurrings = RecurringTransaction::query()
            ->with('category')
            ->orderByDesc('is_active')
            ->orderBy('next_run_on')
            ->get()
            ->map(fn (RecurringTransaction $recurring) => [
                'id' => $recurring->id,
                'type' => $recurring->type->value,
                'amount' => $recurring->amount,
                'description' => $recurring->description,
                'frequency' => $recurring->frequency->value,
                'interval' => $recurring->interval,
                'next_run_on' => $recurring->next_run_on->toDateString(),
                'end_on' => $recurring->end_on?->toDateString(),
                'last_run_on' => $recurring->last_run_on?->toDateString(),
                'is_active' => $recurring->is_active,
                'wallet_id' => $recurring->wallet_id,
                'destination_wallet_id' => $recurring->destination_wallet_id,
                'category_id' => $recurring->category_id,
                'wallet_name' => $walletMap->get($recurring->wallet_id)['name'] ?? '-',
                'destination_wallet_name' => $recurring->destination_wallet_id !== null
                    ? ($walletMap->get($recurring->destination_wallet_id)['name'] ?? '-')
                    : null,
                'category' => $recurring->category === null ? null : [
                    'id' => $recurring->category->id,
                    'name' => $recurring->category->name,
                    'color' => $recurring->category->color,
                    'icon' => $recurring->category->icon,
                ],
            ]);

        return Inertia::render('recurring/index', ['recurrings' => $recurrings]);
    }

    public function store(StoreRecurringRequest $request, CreateRecurring $createRecurring): RedirectResponse
    {
        $validated = $request->validated();
        $type = TransactionType::from($validated['type']);

        $createRecurring->handle(
            userId: $request->user()->id,
            type: $type,
            walletId: (int) $validated['wallet_id'],
            destinationWalletId: $type === TransactionType::Transfer ? (int) $validated['destination_wallet_id'] : null,
            categoryId: $type === TransactionType::Transfer ? null : (int) $validated['category_id'],
            amount: $validated['amount'],
            description: $validated['description'] ?? null,
            frequency: RecurringFrequency::from($validated['frequency']),
            interval: (int) $validated['interval'],
            startOn: $validated['next_run_on'],
            endOn: $validated['end_on'] ?? null,
        );

        return back()->with('success', __('ui.recurring_created'));
    }

    public function update(
        StoreRecurringRequest $request,
        RecurringTransaction $recurring,
        UpdateRecurring $updateRecurring,
    ): RedirectResponse {
        $this->authorize('update', $recurring);

        $validated = $request->validated();
        $type = TransactionType::from($validated['type']);

        $updateRecurring->handle(
            recurring: $recurring,
            type: $type,
            walletId: (int) $validated['wallet_id'],
            destinationWalletId: $type === TransactionType::Transfer ? (int) $validated['destination_wallet_id'] : null,
            categoryId: $type === TransactionType::Transfer ? null : (int) $validated['category_id'],
            amount: $validated['amount'],
            description: $validated['description'] ?? null,
            frequency: RecurringFrequency::from($validated['frequency']),
            interval: (int) $validated['interval'],
            nextRunOn: $validated['next_run_on'],
            endOn: $validated['end_on'] ?? null,
        );

        return back()->with('success', __('ui.recurring_updated'));
    }

    public function toggle(Request $request, RecurringTransaction $recurring, ToggleRecurring $toggleRecurring): RedirectResponse
    {
        $this->authorize('update', $recurring);

        $active = $request->boolean('active');
        $toggleRecurring->handle($recurring, $active);

        return back()->with('success', $active ? __('ui.recurring_resumed') : __('ui.recurring_paused'));
    }

    public function destroy(RecurringTransaction $recurring): RedirectResponse
    {
        $this->authorize('delete', $recurring);

        $recurring->delete();

        return back()->with('success', __('ui.recurring_deleted'));
    }
}
