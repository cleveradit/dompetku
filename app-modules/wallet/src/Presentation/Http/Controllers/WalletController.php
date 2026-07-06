<?php

declare(strict_types=1);

namespace Modules\Wallet\Presentation\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Wallet\Application\Actions\ArchiveWallet;
use Modules\Wallet\Application\Actions\CreateWallet;
use Modules\Wallet\Application\Actions\DeleteWallet;
use Modules\Wallet\Application\Actions\UpdateWallet;
use Modules\Wallet\Domain\Enums\WalletType;
use Modules\Wallet\Domain\Exceptions\WalletHasTransactions;
use Modules\Wallet\Infrastructure\Models\Wallet;
use Modules\Wallet\Presentation\Http\Requests\StoreWalletRequest;
use Modules\Wallet\Presentation\Http\Requests\UpdateWalletRequest;

class WalletController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, DeleteWallet $deleteWallet): Response
    {
        $wallets = Wallet::query()
            ->orderBy('is_archived')
            ->orderBy('name')
            ->get()
            ->map(fn (Wallet $wallet) => [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'type' => $wallet->type->value,
                'type_label' => $wallet->type->label(),
                'current_balance' => $wallet->current_balance,
                'color' => $wallet->color,
                'icon' => $wallet->icon,
                'is_archived' => $wallet->is_archived,
                'deletable' => ! $deleteWallet->isUsed($wallet),
            ]);

        return Inertia::render('wallets/index', ['wallets' => $wallets]);
    }

    public function first(): Response
    {
        return Inertia::render('wallets/first');
    }

    public function store(StoreWalletRequest $request, CreateWallet $createWallet): RedirectResponse
    {
        $validated = $request->validated();

        $createWallet->handle(
            userId: $request->user()->id,
            name: trim($validated['name']),
            type: WalletType::from($validated['type']),
            initialBalance: $validated['initial_balance'],
            color: $validated['color'] ?? null,
            icon: $validated['icon'] ?? null,
        );

        return redirect()->intended(route('dashboard'))->with('success', __('ui.wallet_created'));
    }

    public function update(UpdateWalletRequest $request, Wallet $wallet, UpdateWallet $updateWallet): RedirectResponse
    {
        $this->authorize('update', $wallet);

        $validated = $request->validated();
        $validated['type'] = WalletType::from($validated['type']);
        $validated['name'] = trim($validated['name']);

        $updateWallet->handle($wallet, $validated);

        return back()->with('success', __('ui.wallet_updated'));
    }

    public function archive(Request $request, Wallet $wallet, ArchiveWallet $archiveWallet): RedirectResponse
    {
        $this->authorize('update', $wallet);

        $archived = $request->boolean('archived', true);
        $archiveWallet->handle($wallet, $archived);

        return back()->with('success', $archived ? __('ui.wallet_archived') : __('ui.wallet_unarchived'));
    }

    public function destroy(Wallet $wallet, DeleteWallet $deleteWallet): RedirectResponse
    {
        $this->authorize('delete', $wallet);

        try {
            $deleteWallet->handle($wallet);
        } catch (WalletHasTransactions $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('ui.wallet_deleted'));
    }
}
