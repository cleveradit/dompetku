<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;

/**
 * 04-NFR.md S-9 / AC-03.1: respon UI sama persis untuk email terdaftar maupun
 * tidak, agar tidak bisa dipakai menebak akun (anti user-enumeration).
 */
class IndistinguishablePasswordResetLinkResponse implements FailedPasswordResetLinkRequestResponse, SuccessfulPasswordResetLinkRequestResponse
{
    public function toResponse($request): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['message' => __('passwords.sent')], 200);
        }

        return back()->with('status', __('passwords.sent'));
    }
}
