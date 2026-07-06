<?php

declare(strict_types=1);

namespace Modules\Wallet\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Wallet\Infrastructure\Models\Wallet;
use Symfony\Component\HttpFoundation\Response;

/**
 * 00-PRD.md §7 onboarding: user terverifikasi tanpa dompet diarahkan ke layar
 * "Buat dompet pertamamu" sebelum bisa memakai aplikasi.
 */
class EnsureHasWallet
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->hasVerifiedEmail() && ! Wallet::query()->exists()) {
            return redirect()->route('wallets.first');
        }

        return $next($request);
    }
}
