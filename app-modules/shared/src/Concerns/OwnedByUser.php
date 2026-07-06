<?php

declare(strict_types=1);

namespace Modules\Shared\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * 04-NFR.md §1: every query on user-owned tables is filtered by the
 * authenticated user. Actions running outside a request (scheduler, queue)
 * must scope queries explicitly — the global scope only applies when a user
 * is authenticated.
 */
trait OwnedByUser
{
    public static function bootOwnedByUser(): void
    {
        static::addGlobalScope('ownedByUser', function (Builder $builder): void {
            if (Auth::hasUser()) {
                $builder->where($builder->getModel()->getTable().'.user_id', Auth::id());
            }
        });
    }
}
