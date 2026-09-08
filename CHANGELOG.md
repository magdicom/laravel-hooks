# Changelog

All notable changes to `laravel-hooks` will be documented in this file.

## v2.0.0-beta.2 - 2026-09-08

This prerelease corrects the beta installation and packaging guidance. No runtime API or behavior changed from `v2.0.0-beta.1`.

Because both the Laravel wrapper and core package are prereleases, beta consumers must explicitly permit both packages:

```bash
composer require magdicom/laravel-hooks:"^2.0@beta" magdicom/hooks:"^2.0@beta"
```

Composer's dependency-level stability flags do not override the consumer application's default stable policy. There is no need to change the application's global `minimum-stability` setting.

## v2.0.0-beta.1 - 2026-09-07

Version 2 is intentionally breaking. See [UPGRADE.md](UPGRADE.md) for migration guidance from `1.x`.

### Changed

- Reworked the package as a clean Laravel wrapper around the published `magdicom/hooks` version-2 beta action, filter, and collector models.
- Added support for collector processors and renderers through the core package API.
- Added Laravel 12 and Laravel 13 support on PHP `^8.2`.
- Registered one shared core `Magdicom\Hooks` singleton in Laravel's container, available through `Magdicom\Hooks::class`, `app('hooks')`, the `hooks()` helper, and the `Hooks` facade.
- Updated the facade and helper to expose the core version-2 actions, filters, collectors, processors, renderers, registration, inspection, and removal APIs.
- Added a Laravel-container-backed `Magdicom\Resolver` implementation for non-static class callbacks, class-name processors, and class-name renderers.
- Bound `Magdicom\Resolver` as a replaceable singleton so applications can provide custom class-name resolution before the shared hooks instance is resolved.
- Replaced the version-1 test suite with Laravel integration coverage for the wrapper boundary.
- Modernized Composer metadata, static analysis, formatting, and CI checks.

### Removed

- Removed the version-1 `register()`, `all()`, `first()`, `last()`, `toArray()`, `toString()`, shared output, and global parameter behavior.
- Removed Laravel 9, 10, and 11 support from the version-2 branch.
- Removed unsafe automatic workflow commits and the `pull_request_target` Dependabot auto-merge workflow.

## v1.0.0 - 2021-12-31

- initial release
