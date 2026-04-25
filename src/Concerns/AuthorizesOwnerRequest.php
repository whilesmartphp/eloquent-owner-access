<?php

namespace Whilesmart\OwnerAccess\Concerns;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

/**
 * For use on FormRequest classes. Provides two helpers that consult the
 * bound OwnerAuthorizer:
 *
 * - authorizeOwnerInRequest(): authorize against owner fields posted in the
 *   request body. Use in StoreXRequest::authorize().
 * - authorizeOwnerOfBoundModel(): authorize against the owner of a route-
 *   bound model. Use in UpdateXRequest::authorize().
 */
trait AuthorizesOwnerRequest
{
    /**
     * Defers to validation when the owner fields aren't present, so a
     * missing owner surfaces a 422 rather than a 403.
     */
    protected function authorizeOwnerInRequest(
        string $ownerTypeKey = 'owner_type',
        string $ownerIdKey = 'owner_id',
    ): bool {
        $ownerType = $this->input($ownerTypeKey);
        $ownerId = $this->input($ownerIdKey);

        if (! is_string($ownerType) || $ownerType === '' || $ownerId === null || $ownerId === '') {
            return true;
        }

        return app(OwnerAuthorizer::class)
            ->authorize($this->user(), $ownerType, $ownerId);
    }

    /**
     * Yields the standard 404 path when the model isn't bound on the route.
     */
    protected function authorizeOwnerOfBoundModel(
        string $routeKey,
        string $ownerTypeAttr = 'owner_type',
        string $ownerIdAttr = 'owner_id',
    ): bool {
        $model = $this->route($routeKey);

        if (! $model instanceof Model) {
            return true;
        }

        return app(OwnerAuthorizer::class)->authorize(
            $this->user(),
            (string) $model->{$ownerTypeAttr},
            $model->{$ownerIdAttr},
        );
    }
}
