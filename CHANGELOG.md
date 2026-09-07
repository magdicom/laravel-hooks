# Changelog

All notable changes to `laravel-hooks` will be documented in this file.

## Unreleased

### Changed

- Reworked the package for `magdicom/hooks` version 2 beta.
- Raised requirements to PHP `^8.2` and Laravel `^12.0 || ^13.0`.
- Registered one shared core `Magdicom\Hooks` singleton in Laravel's container.
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
