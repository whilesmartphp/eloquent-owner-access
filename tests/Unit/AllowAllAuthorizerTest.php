<?php

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use Whilesmart\OwnerAccess\Authorizers\AllowAllAuthorizer;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

class AllowAllAuthorizerTest extends TestCase
{
    public function test_authorize_always_returns_true(): void
    {
        $authorizer = new AllowAllAuthorizer;

        $this->assertTrue($authorizer->authorize(null, 'App\\Models\\Workspace', 1));
        $this->assertTrue($authorizer->authorize(null, 'whatever', 'string-id'));
    }

    public function test_scope_returns_query_unmodified(): void
    {
        $authorizer = new AllowAllAuthorizer;
        $builder = $this->createMock(Builder::class);

        $this->assertSame(
            $builder,
            $authorizer->scope($builder, null)
        );
    }

    public function test_service_provider_binds_default_authorizer(): void
    {
        $this->assertInstanceOf(
            AllowAllAuthorizer::class,
            $this->app->make(OwnerAuthorizer::class)
        );
    }

    public function test_service_provider_does_not_override_existing_binding(): void
    {
        $custom = new class implements OwnerAuthorizer
        {
            public function authorize(?\Illuminate\Contracts\Auth\Authenticatable $user, string $ownerType, mixed $ownerId): bool
            {
                return false;
            }

            public function scope(Builder $query, ?\Illuminate\Contracts\Auth\Authenticatable $user, string $ownerTypeColumn = 'owner_type', string $ownerIdColumn = 'owner_id'): Builder
            {
                return $query;
            }
        };

        $this->app->instance(OwnerAuthorizer::class, $custom);

        // Re-register the provider; bindIf must be a no-op when bound.
        (new \Whilesmart\OwnerAccess\OwnerAccessServiceProvider($this->app))->register();

        $this->assertSame($custom, $this->app->make(OwnerAuthorizer::class));
    }

    /** Stand-in test model for type hinting; not used for DB. */
    private function fakeModel(): Model
    {
        return new class extends Model {};
    }
}
