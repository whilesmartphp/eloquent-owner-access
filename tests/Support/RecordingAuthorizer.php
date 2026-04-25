<?php

namespace Tests\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

/**
 * Test authorizer that records every call and lets each test decide whether
 * to allow or deny. Allows assertions on the exact (user, ownerType, ownerId)
 * tuple the trait forwarded.
 */
class RecordingAuthorizer implements OwnerAuthorizer
{
    /** @var list<array{user: ?Authenticatable, ownerType: string, ownerId: mixed}> */
    public array $authorizeCalls = [];

    /** @var list<array{user: ?Authenticatable, ownerTypeColumn: string, ownerIdColumn: string}> */
    public array $scopeCalls = [];

    public function __construct(
        public bool $allow = true,
        public ?\Closure $scopeUsing = null,
    ) {}

    public function authorize(?Authenticatable $user, string $ownerType, mixed $ownerId): bool
    {
        $this->authorizeCalls[] = [
            'user' => $user,
            'ownerType' => $ownerType,
            'ownerId' => $ownerId,
        ];

        return $this->allow;
    }

    public function scope(
        Builder $query,
        ?Authenticatable $user,
        string $ownerTypeColumn = 'owner_type',
        string $ownerIdColumn = 'owner_id',
    ): Builder {
        $this->scopeCalls[] = [
            'user' => $user,
            'ownerTypeColumn' => $ownerTypeColumn,
            'ownerIdColumn' => $ownerIdColumn,
        ];

        if ($this->scopeUsing !== null) {
            return ($this->scopeUsing)($query, $user, $ownerTypeColumn, $ownerIdColumn);
        }

        return $query;
    }
}
