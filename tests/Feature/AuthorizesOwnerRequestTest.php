<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\Support\OwnedThing;
use Tests\Support\OwnedThingController;
use Tests\Support\RecordingAuthorizer;
use Tests\TestCase;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

class AuthorizesOwnerRequestTest extends TestCase
{
    protected RecordingAuthorizer $authorizer;

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('owned_things', function ($table) {
            $table->id();
            $table->string('owner_type');
            $table->unsignedBigInteger('owner_id');
            $table->string('label')->nullable();
            $table->timestamps();
        });
    }

    protected function defineRoutes($router): void
    {
        Route::middleware('api')->group(function () {
            Route::post('/things', [OwnedThingController::class, 'store']);
            Route::put('/things/{thing}', [OwnedThingController::class, 'update']);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizer = new RecordingAuthorizer;
        $this->app->instance(OwnerAuthorizer::class, $this->authorizer);
    }

    public function test_store_request_forwards_owner_to_authorizer(): void
    {
        $this->postJson('/things', [
            'owner_type' => 'App\\Models\\Workspace',
            'owner_id' => 42,
            'label' => 'thing-1',
        ])->assertCreated();

        $this->assertCount(1, $this->authorizer->authorizeCalls);
        $this->assertSame('App\\Models\\Workspace', $this->authorizer->authorizeCalls[0]['ownerType']);
        $this->assertSame(42, $this->authorizer->authorizeCalls[0]['ownerId']);
    }

    public function test_store_request_returns_403_when_authorizer_denies(): void
    {
        $this->authorizer->allow = false;

        $this->postJson('/things', [
            'owner_type' => 'App\\Models\\Workspace',
            'owner_id' => 42,
            'label' => 'thing-1',
        ])->assertForbidden();
    }

    public function test_store_request_yields_validation_when_owner_fields_are_missing(): void
    {
        $response = $this->postJson('/things', [
            'label' => 'thing-1',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['owner_type', 'owner_id']);

        $this->assertSame([], $this->authorizer->authorizeCalls,
            'Authorizer should not be consulted when owner fields are missing.');
    }

    public function test_update_request_forwards_owner_of_bound_model(): void
    {
        $thing = OwnedThing::create([
            'owner_type' => 'App\\Models\\Workspace',
            'owner_id' => 7,
            'label' => 'before',
        ]);

        $this->putJson("/things/{$thing->id}", ['label' => 'after'])
            ->assertOk();

        $this->assertCount(1, $this->authorizer->authorizeCalls);
        $this->assertSame('App\\Models\\Workspace', $this->authorizer->authorizeCalls[0]['ownerType']);
        $this->assertSame('7', (string) $this->authorizer->authorizeCalls[0]['ownerId']);
    }

    public function test_update_request_returns_403_when_authorizer_denies(): void
    {
        $thing = OwnedThing::create([
            'owner_type' => 'App\\Models\\Workspace',
            'owner_id' => 7,
            'label' => 'before',
        ]);

        $this->authorizer->allow = false;

        $this->putJson("/things/{$thing->id}", ['label' => 'after'])
            ->assertForbidden();

        $this->assertSame('before', $thing->fresh()->label);
    }
}
