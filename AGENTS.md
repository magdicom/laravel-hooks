# Repository Instructions

This package is the Laravel integration layer for `magdicom/hooks`. Keep it thin.

## Architecture

- Treat `magdicom/hooks` as the source of truth for hook registration, execution, priorities, inspection, removal, processing, and rendering.
- Do not duplicate core hook behavior in this repository.
- Do not add a second registry, endpoint-definition system, dispatcher abstraction, observers, queues, asynchronous hooks, wildcard endpoints, automatic Laravel event bridging, Blade directives, or documentation-site code.
- The Laravel wrapper should expose only framework integration:
  - a service provider;
  - a Laravel-container-backed implementation of `Magdicom\Resolver`;
  - the `Magdicom\LaravelHooks\Facades\Hooks` facade;
  - the global `hooks()` helper;
  - Laravel-specific documentation and integration tests.

## Version 2 Direction

- Version 2 is intentionally breaking and targets the published `magdicom/hooks` v2 beta line.
- Do not preserve the v1 wrapper API through compatibility aliases, forwarding machinery, or silent behavior changes.
- Removed v1 behavior belongs in `UPGRADE.md`, not in runtime shims.
- The helper should return the shared core `Magdicom\Hooks` singleton. It must not maintain global parameter state.
- Old helper calls such as `hooks($parameters)` should fail clearly instead of appearing to work.

## Container Integration

- Register one shared `Magdicom\Hooks` instance in Laravel's container.
- Bind `Magdicom\Resolver` as a real singleton binding, not as an alias, so applications can replace it before `Magdicom\Hooks` is resolved.
- The class binding, `hooks` string alias, facade root, and `hooks()` helper must resolve the same object.
- Resolve class-name callbacks, processors, and renderers through Laravel's container via the Laravel resolver.
- Do not fall back to `new $className` inside the Laravel resolver.
- Do not catch and hide Laravel container exceptions.

## Tests

- Test the Laravel integration boundary:
  - service-provider registration;
  - singleton identity across all access paths;
  - facade and helper access;
  - container-resolved callbacks, processors, and renderers;
  - registration handles, inspection, and removal through Laravel access points.
- Avoid duplicating the full core package test suite. Core semantics should remain covered by `magdicom/hooks`.
- Prefer focused fixtures in tests when they demonstrate Laravel container behavior.
- When possible, include concise manual testing instructions in task notes, pull requests, or completion reports so reviewers can verify the changed behavior directly.
- Manual testing instructions should cover at least one facade call, one helper call, singleton identity across `app(\Magdicom\Hooks::class)`, `app('hooks')`, `hooks()`, and `Hooks::getFacadeRoot()`, plus one container-resolved non-static class callback with constructor dependencies.
- For dependency or compatibility changes, include the exact Laravel, Testbench, PHP, and dependency-stability combination tested.

## Code Quality

- Use `declare(strict_types=1)` in PHP files.
- Keep public APIs explicitly typed.
- Keep PHPStan/Larastan configuration strict and avoid broad ignore rules.
- Use repository Composer scripts for validation. Keep these scripts current as the tool stack changes:
  - `composer validate --strict`
  - `composer test`
  - `composer analyse`
  - `composer format -- --dry-run`

## Dependencies

- Runtime dependencies should reflect the supported Laravel wrapper matrix, not accidental dev-package availability.
- Require the Illuminate packages that the wrapper directly uses.
- Do not retain obsolete Laravel support in v2.
- Keep dependency stability explicit and as narrow as practical; avoid package-wide `minimum-stability: dev` unless there is a demonstrated need.

## Documentation

- README content should describe the Laravel wrapper, not re-document every core feature.
- Link to the core package for core API details.
- Keep `UPGRADE.md` current for breaking changes from v1 to v2.
- Document singleton lifecycle implications for normal Laravel bootstrapping and long-running processes without adding speculative lifecycle machinery.

## Project Workflow

- When a task is implementation-complete, move the project item to `In review`.
- Do not move completed implementation work directly to `Done`; reserve `Done` for after review or explicit maintainer confirmation.
