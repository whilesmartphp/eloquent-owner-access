<?php

namespace Whilesmart\OwnerAccess\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

/**
 * For use on resource controllers. Provides a per-record authorization
 * helper for show/destroy/custom actions, plus a query scope helper for
 * index. Both consult the bound OwnerAuthorizer.
 */
trait AuthorizesOwnerController
{
    /**
     * Throws 403 when $user is not allowed to access the polymorphic owner
     * of $record.
     */
    protected function authorizeAccessTo(
        Model $record,
        ?Authenticatable $user,
        string $ownerTypeAttr = 'owner_type',
        string $ownerIdAttr = 'owner_id',
    ): void {
        $allowed = app(OwnerAuthorizer::class)->authorize(
            $user,
            (string) $record->{$ownerTypeAttr},
            $record->{$ownerIdAttr},
        );

        if (! $allowed) {
            throw new AccessDeniedHttpException;
        }
    }

    /**
     * Constrain $query to records whose owner $user may access.
     */
    protected function scopeAccessibleOwners(
        Builder $query,
        ?Authenticatable $user,
        string $ownerTypeColumn = 'owner_type',
        string $ownerIdColumn = 'owner_id',
    ): Builder {
        return app(OwnerAuthorizer::class)
            ->scope($query, $user, $ownerTypeColumn, $ownerIdColumn);
    }
}
