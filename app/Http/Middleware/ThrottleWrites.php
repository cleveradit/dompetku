<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 04-NFR.md S-2: register & lupa-password 3/menit per IP; endpoint tulis
 * 60/menit per user; upload 10/menit per user. Login memakai limiter Fortify.
 */
class ThrottleWrites
{
    private const AUTH_PATHS = ['register', 'forgot-password'];

    public function __construct(private readonly RateLimiter $limiter)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isWrite($request)) {
            return $next($request);
        }

        [$key, $maxAttempts] = $this->limitFor($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            throw new ThrottleRequestsException(__('ui.throttled'));
        }

        $this->limiter->hit($key, 60);

        return $next($request);
    }

    private function isWrite(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    /** @return array{0: string, 1: int} */
    private function limitFor(Request $request): array
    {
        $path = trim($request->path(), '/');

        if (in_array($path, self::AUTH_PATHS, true)) {
            return ['auth-writes:'.$request->ip(), 3];
        }

        $subject = $request->user()?->id !== null ? 'user:'.$request->user()->id : 'ip:'.$request->ip();

        if ($request->hasFile('file') || str_contains($path, 'attachments') || str_contains($path, 'import')) {
            return ['uploads:'.$subject, 10];
        }

        return ['writes:'.$subject, 60];
    }
}
