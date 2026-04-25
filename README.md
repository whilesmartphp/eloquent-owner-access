# eloquent-owner-access

Pluggable polymorphic owner authorization for Laravel packages with `owner_type` / `owner_id` columns.

Domain packages (accounts, expenses, payments, invoices, products, customers, ...) expose CRUD endpoints scoped by a polymorphic owner. They should not have to know how the host app decides who owns what. This package gives them a single contract to consult and a default that preserves existing "trust the client" behavior, so adoption is incremental and safe.

## Concept

One contract:

```php
interface OwnerAuthorizer
{
    public function authorize(?Authenticatable $user, string $ownerType, mixed $ownerId): bool;

    public function scope(
        Builder $query,
        ?Authenticatable $user,
        string $ownerTypeColumn = 'owner_type',
        string $ownerIdColumn = 'owner_id',
    ): Builder;
}
```

`authorize()` is per-record (called from `FormRequest::authorize()` for store/update and from controllers for show/destroy). `scope()` constrains index queries.

The default binding is `AllowAllAuthorizer` (everything goes through). Hosts that want strict tenancy bind their own implementation.

## Installation

```bash
composer require whilesmart/eloquent-owner-access
```

The service provider auto-registers via Laravel package discovery and `bindIf`s the default authorizer.

## Usage in a package

In a `StoreXRequest`:

```php
use Whilesmart\OwnerAccess\Concerns\AuthorizesOwnerRequest;

class StoreAccountRequest extends FormRequest
{
    use AuthorizesOwnerRequest;

    public function authorize(): bool
    {
        return $this->authorizeOwnerInRequest();
    }

    public function rules(): array { /* ... */ }
}
```

In an `UpdateXRequest`:

```php
public function authorize(): bool
{
    return $this->authorizeOwnerOfBoundModel('account');
}
```

In the controller:

```php
use Whilesmart\OwnerAccess\Concerns\AuthorizesOwnerController;

class AccountController extends Controller
{
    use AuthorizesOwnerController;

    public function index(Request $request)
    {
        $query = Account::query();
        $query = $this->scopeAccessibleOwners($query, $request->user());
        // ...
    }

    public function show(Account $account, Request $request)
    {
        $this->authorizeAccessTo($account, $request->user());
        // ...
    }

    public function destroy(Account $account, Request $request)
    {
        $this->authorizeAccessTo($account, $request->user());
        $account->delete();
        // ...
    }
}
```

Update is already covered by `UpdateXRequest::authorize()`, so no controller change is needed there.

## Usage in a host app

By default everything is allowed. To enforce tenancy, write an authorizer that consults your tenancy model and bind it:

```php
namespace App\Authorization;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

class WorkspaceMemberAuthorizer implements OwnerAuthorizer
{
    public function authorize(?Authenticatable $user, string $ownerType, mixed $ownerId): bool
    {
        if ($user === null || $ownerType !== \App\Models\Workspace::class) {
            return false;
        }

        return $user->workspaces()->whereKey($ownerId)->exists();
    }

    public function scope(Builder $query, ?Authenticatable $user, string $ownerTypeColumn = 'owner_type', string $ownerIdColumn = 'owner_id'): Builder
    {
        if ($user === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where($ownerTypeColumn, \App\Models\Workspace::class)
            ->whereIn($ownerIdColumn, $user->workspaces()->pluck('workspaces.id'));
    }
}
```

Then in `AppServiceProvider::register()`:

```php
$this->app->bind(
    \Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer::class,
    \App\Authorization\WorkspaceMemberAuthorizer::class,
);
```

That single binding switches every consuming package over to strict mode at once.

## Backwards compatibility

The provider uses `bindIf`, so any explicit binding the host registers (before or after) wins. Without an explicit binding, the default `AllowAllAuthorizer` keeps current behavior.

## Testing

```bash
composer test
```

## License

MIT.
