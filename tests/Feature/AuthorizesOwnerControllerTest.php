<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\Support\OwnedThing;
use Tests\Support\OwnedThingController;
use Tests\Support\RecordingAuthorizer;
use Tests\TestCase;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

class AuthorizesOwnerControllerTest extends TestCase
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
            Route::get('/things', [OwnedThingController::class, 'index']);
            Route::get('/things/{thing}', [OwnedThingController::class, 'show']);
            Route::delete('/things/{thing}', [OwnedThingController::class, 'destroy']);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizer = new RecordingAuthorizer;
        $this->app->instance(OwnerAuthorizer::class, $this->authorizer);
    }

    public function test_show_authorizes_against_record_owner(): void
    {
        $thing = $this->makeThing(7);

        $this->getJson("/things/{$thing->id}")->assertOk();

        $this->assertCount(1, $this->authorizer->authorizeCalls);
        $this->assertSame('App\\Models\\Workspace', $this->authorizer->authorizeCalls[0]['ownerType']);
        $this->assertSame(7, (int) $this->authorizer->authorizeCalls[0]['ownerId']);
    }

    public function test_show_returns_403_when_authorizer_denies(): void
    {
        $thing = $this->makeThing(7);
        $this->authorizer->allow = false;

        $this->getJson("/things/{$thing->id}")->assertForbidden();
    }

    public function test_destroy_authorizes_before_deleting(): void
    {
        $thing = $this->makeThing(7);
        $this->authorizer->allow = false;

        $this->deleteJson("/things/{$thing->id}")->assertForbidden();

        $this->assertNotNull($thing->fresh(),
            'Record must not be deleted when authorizer denies.');
    }

    public function test_index_calls_scope_with_authenticated_user(): void
    {
        $this->makeThing(7);
        $this->makeThing(8);

        $this->getJson('/things')->assertOk();

        $this->assertCount(1, $this->authorizer->scopeCalls);
        $this->assertSame('owner_type', $this->authorizer->scopeCalls[0]['ownerTypeColumn']);
        $this->assertSame('owner_id', $this->authorizer->scopeCalls[0]['ownerIdColumn']);
    }

    public function test_index_applies_scope_constraints(): void
    {
        $allowed = $this->makeThing(7);
        $denied = $this->makeThing(8);

        $this->authorizer->scopeUsing = function ($query) {
            return $query->where('owner_id', 7);
        };

        $response = $this->getJson('/things')->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($allowed->id, $ids);
        $this->assertNotContains($denied->id, $ids);
    }

    private function makeThing(int $ownerId): OwnedThing
    {
        return OwnedThing::create([
            'owner_type' => 'App\\Models\\Workspace',
            'owner_id' => $ownerId,
            'label' => 'thing',
        ]);
    }
}
