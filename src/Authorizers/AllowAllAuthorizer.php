<?php

namespace Whilesmart\OwnerAccess\Authorizers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

/**
 * Default authorizer. Lets every authenticated request through and applies
 * no scoping. This preserves backwards compatibility for hosts that have
 * not yet bound a strict authorizer.
 */
class AllowAllAuthorizer implements OwnerAuthorizer
{
    public function authorize(?Authenticatable $user, string $ownerType, mixed $ownerId): bool
    {
        return true;
    }

    public function scope(
        Builder $query,
        ?Authenticatable $user,
        string $ownerTypeColumn = 'owner_type',
        string $ownerIdColumn = 'owner_id',
    ): Builder {
        return $query;
    }
}
