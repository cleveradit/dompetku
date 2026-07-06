<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Modules\Ledger\Application\Queries\TransactionFormOptionsQuery;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            // Data form transaksi global (FAB, 05-DESIGN.md 4.5) untuk user terverifikasi.
            'transactionForm' => $user !== null && $user->hasVerifiedEmail()
                ? fn () => app(TransactionFormOptionsQuery::class)->handle($user->id)
                : null,
            'name' => config('app.name'),
            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'currency' => $user->currency,
                    'email_verified_at' => $user->email_verified_at?->toISOString(),
                ],
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'status' => $request->session()->get('status'),
        ]);
    }
}
