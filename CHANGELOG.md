# Changelog

All notable changes to `whilesmart/eloquent-owner-access` are documented here.

## [1.0.0] - 2026-04-25

- Initial release
- `OwnerAuthorizer` contract with `authorize()` and `scope()` methods
- `AllowAllAuthorizer` default implementation, registered via `bindIf` so hosts can override
- `AuthorizesOwnerRequest` FormRequest trait with `authorizeOwnerInRequest()` and `authorizeOwnerOfBoundModel()`
- `AuthorizesOwnerController` controller trait with `authorizeAccessTo()` and `scopeAccessibleOwners()`
- Auto-registered service provider via Laravel package discovery
