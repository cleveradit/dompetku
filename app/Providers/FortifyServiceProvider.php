<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\IndistinguishablePasswordResetLinkResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;
use Modules\Shared\Support\Currencies;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 04-NFR.md S-9: anti user-enumeration pada lupa password.
        $this->app->singleton(
            SuccessfulPasswordResetLinkRequestResponse::class,
            IndistinguishablePasswordResetLinkResponse::class,
        );
        $this->app->singleton(
            FailedPasswordResetLinkRequestResponse::class,
            IndistinguishablePasswordResetLinkResponse::class,
        );
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        $this->registerViews();
        $this->registerRateLimiters();
    }

    private function registerViews(): void
    {
        Fortify::loginView(fn () => Inertia::render('auth/login', [
            'canResetPassword' => true,
            'status' => session('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/register', [
            'currencies' => Currencies::options(),
            'defaultCurrency' => Currencies::DEFAULT,
        ]));

        Fortify::requestPasswordResetLinkView(fn () => Inertia::render('auth/forgot-password', [
            'status' => session('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->input('email'),
            'token' => $request->route('token'),
        ]));

        Fortify::verifyEmailView(fn () => Inertia::render('auth/verify-email', [
            'status' => session('status'),
        ]));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    private function registerRateLimiters(): void
    {
        // 04-NFR.md S-2: login 5/menit per email+IP.
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        // AC-01.4: kirim ulang email verifikasi maksimal 1x per menit.
        RateLimiter::for('verification', function (Request $request) {
            return Limit::perMinute(1)->by((string) ($request->user()?->id ?: $request->ip()));
        });
    }
}
