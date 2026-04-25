<?php

namespace Whilesmart\OwnerAccess;

use Illuminate\Support\ServiceProvider;
use Whilesmart\OwnerAccess\Authorizers\AllowAllAuthorizer;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

class OwnerAccessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bindIf(OwnerAuthorizer::class, AllowAllAuthorizer::class);
    }
}
