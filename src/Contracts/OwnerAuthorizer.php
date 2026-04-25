<?php

namespace Whilesmart\OwnerAccess\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

interface OwnerAuthorizer
{
    /**
     * Decide whether $user is allowed to access records belonging to the
     * given polymorphic owner. Called by FormRequest::authorize() during
     * store/update, and by controllers when a single record is loaded.
     */
    public function authorize(?Authenticatable $user, string $ownerType, mixed $ownerId): bool;

    /**
     * Constrain $query to only records whose owner $user may access. Called
     * by controller index methods. Implementations may apply whereIn,
     * subqueries, or raw conditions; AllowAllAuthorizer leaves $query
     * untouched.
     */
    public function scope(
        Builder $query,
        ?Authenticatable $user,
        string $ownerTypeColumn = 'owner_type',
        string $ownerIdColumn = 'owner_id',
    ): Builder;
}
