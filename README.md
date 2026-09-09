# Hooks for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/magdicom/laravel-hooks.svg?style=flat-square)](https://packagist.org/packages/magdicom/laravel-hooks)
[![Total Downloads](https://img.shields.io/packagist/dt/magdicom/laravel-hooks.svg?style=flat-square)](https://packagist.org/packages/magdicom/laravel-hooks)
[![CI](https://github.com/magdicom/laravel-hooks/actions/workflows/ci.yml/badge.svg?branch=2.0)](https://github.com/magdicom/laravel-hooks/actions/workflows/ci.yml?query=branch%3A2.0)

`magdicom/laravel-hooks` is Hooks for Laravel: first-class Laravel integration for the framework-independent [`magdicom/hooks`](https://github.com/magdicom/hooks) core, providing extension points for PHP applications.

Use `hooks()` as the concise application-level access method. Inside services, jobs, and commands, constructor injection of `Magdicom\Hooks` is recommended. The `Hooks` facade is an optional Laravel convenience when facade-style access fits the calling code.

Laravel's container resolves supported non-static class callbacks, class-name processors, and class-name renderers, including their constructor dependencies. Complete documentation is available at [hooks.momagdi.com](https://hooks.momagdi.com), including the [Laravel integration guide](https://hooks.momagdi.com/docs/2.x/laravel/).

Core hook behavior belongs to `magdicom/hooks`: actions, filters, collectors, priorities, registration handles, inspection, removal, result processors, and renderers.

Version 2 is intentionally breaking. If you are upgrading from `1.x`, read [UPGRADE.md](UPGRADE.md).

## Requirements

- PHP `^8.2`
- Laravel `^12.0` or `^13.0`
- `magdicom/hooks` `^2.0.0-beta.2`

Laravel 9, 10, and 11 are not supported by this version-2 branch.

## Installation

Install the beta release:

```bash
composer require magdicom/laravel-hooks:"^2.0@beta" magdicom/hooks:"^2.0@beta"
```

The core package is listed explicitly because beta consumers must permit both prerelease packages. Composer's dependency-level stability flags do not override the root project's default stable policy. Do not change your application's global `minimum-stability` setting for this package.

Laravel package auto-discovery registers the service provider and facade alias automatically.

Manual registration is only needed if your application disables package discovery:

```php
'providers' => [
    Magdicom\LaravelHooks\ServiceProvider::class,
],

'aliases' => [
    'Hooks' => Magdicom\LaravelHooks\Facades\Hooks::class,
],
```

## Access

All Laravel access paths resolve the same application singleton:

```php
use Magdicom\Hooks as CoreHooks;
use Magdicom\LaravelHooks\Facades\Hooks;

app(CoreHooks::class);
app('hooks');
hooks();
Hooks::getFacadeRoot();
```

Registrations added through one path are visible through every other path.

Prefer constructor injection in services, jobs, and commands:

```php
use Magdicom\Hooks;

final readonly class InvoiceNotifier
{
    public function __construct(private Hooks $hooks) {}

    public function notify(int $invoiceId): void
    {
        $this->hooks->doAction('invoice.paid', $invoiceId);
    }
}
```

For concise application-level calls, use the `hooks()` helper:

```php
hooks()->doAction('invoice.paid', $invoiceId);
$label = hooks()->applyFilters('invoice.label', $label, $invoiceId);
```

The package also binds `Magdicom\Resolver` to `Magdicom\LaravelHooks\LaravelResolver` as a singleton. Applications and packages may replace the `Magdicom\Resolver` binding before `Magdicom\Hooks` is first resolved when they need custom class-name resolution behavior.

## Facade

```php
use Magdicom\LaravelHooks\Facades\Hooks;

Hooks::addAction('orders.created', function (int $orderId): void {
    // side effect
});

Hooks::doAction('orders.created', $orderId);
```

The facade resolves the core `Magdicom\Hooks` binding. Its public API is the core package API.

## Helper

The `hooks()` helper returns the shared core instance:

```php
hooks()->addFilter('orders.reference', fn (string $value): string => strtoupper($value));

$reference = hooks()->applyFilters('orders.reference', 'draft-100');
```

The helper does not accept parameters and does not maintain global parameter state. Invocation arguments belong on the core dispatch methods:

```php
hooks()->doAction('endpoint', $argument);
hooks()->applyFilters('endpoint', $value, $argument);
hooks()->collect('endpoint', $argument);
```

Old version-1 calls such as `hooks($parameters)` fail with an informative exception.

## Actions

Actions are ordered side-effect hooks. Callback return values are ignored.

```php
use Magdicom\LaravelHooks\Facades\Hooks;

Hooks::addAction('invoice.paid', function (int $invoiceId, string $source): void {
    activity()->log("Invoice {$invoiceId} was paid from {$source}.");
}, priority: 10);

Hooks::doAction('invoice.paid', $invoiceId, 'checkout');
```

## Filters

Filters transform a value sequentially. Each listener receives the current value first, followed by explicit invocation arguments.

```php
use Magdicom\LaravelHooks\Facades\Hooks;

Hooks::addFilter('invoice.label', fn (string $label): string => trim($label), priority: 5);
Hooks::addFilter('invoice.label', fn (string $label, string $suffix): string => $label . $suffix, priority: 20);

$label = Hooks::applyFilters('invoice.label', ' Draft ', ' #100');
```

If no filter listeners exist, the original value is returned.

## Collectors

Collectors gather one raw result from each listener.

```php
use Magdicom\LaravelHooks\Facades\Hooks;

Hooks::addCollector('dashboard.widgets', fn (): array => ['name' => 'Revenue'], priority: 10);
Hooks::addCollector('dashboard.widgets', fn (): array => ['name' => 'Churn'], priority: 20);

$widgets = Hooks::collect('dashboard.widgets');
```

If no collector listeners exist, `collect()` returns an empty array.

## Dependency-Injected Class Callbacks

Non-static class callbacks resolve through Laravel's container, so constructor injection and bindings work normally.

```php
use App\Services\AuditLog;
use Magdicom\LaravelHooks\Facades\Hooks;

final readonly class RecordInvoicePayment
{
    public function __construct(
        private AuditLog $auditLog,
    ) {}

    public function handle(int $invoiceId): void
    {
        $this->auditLog->record('invoice.paid', $invoiceId);
    }
}

Hooks::addAction('invoice.paid', [RecordInvoicePayment::class, 'handle']);
Hooks::doAction('invoice.paid', $invoiceId);
```

Static callable methods follow the core package's callable behavior and do not require unnecessary container instance resolution.

## Processors

Processors finalize collector results while preserving raw `collect()` access.

```php
use Magdicom\LaravelHooks\Facades\Hooks;
use Magdicom\Processors\LastProcessor;

Hooks::addCollector('invoice.status', fn (): string => 'draft');
Hooks::addCollector('invoice.status', fn (): string => 'paid');

Hooks::setProcessor('invoice.status', new LastProcessor());

$raw = Hooks::collect('invoice.status'); // ['draft', 'paid']
$status = Hooks::process('invoice.status'); // 'paid'
```

Class-name processors resolve through Laravel's container and must implement `Magdicom\ResultProcessor`.

If you replace the `Magdicom\Resolver` binding before the shared hooks instance is resolved, class-name processors use that custom resolver.

## Renderers

Renderers are string-producing collector processors.

```php
use Magdicom\LaravelHooks\Facades\Hooks;
use Magdicom\Processors\ConcatenateRenderer;

Hooks::addCollector('layout.footer', fn (): string => '<span>Terms</span>');
Hooks::addCollector('layout.footer', fn (): string => '<span>Privacy</span>');

Hooks::setRenderer('layout.footer', new ConcatenateRenderer("\n"));

$html = Hooks::render('layout.footer');
```

Class-name renderers resolve through Laravel's container and must implement `Magdicom\Renderer`.

If you replace the `Magdicom\Resolver` binding before the shared hooks instance is resolved, class-name renderers use that custom resolver.

## One-Off Processing And Rendering

Use `processWith()` or `renderWith()` when a processor or renderer applies to one invocation without replacing the endpoint's persistent configuration:

```php
use Magdicom\Processors\LastProcessor;

$status = hooks()->processWith('invoice.status', new LastProcessor(), $invoiceId);
$html = hooks()->renderWith('invoice.summary', new InvoiceSummaryRenderer(), $invoiceId);
```

The same core methods are available through the optional facade:

```php
$status = Hooks::processWith('invoice.status', new LastProcessor(), $invoiceId);
$html = Hooks::renderWith('invoice.summary', new InvoiceSummaryRenderer(), $invoiceId);
```

## Registration Handles

Registration methods return `Magdicom\RegistrationHandle`.

```php
use Magdicom\LaravelHooks\Facades\Hooks;

$handle = Hooks::addAction('orders.created', $callback);

Hooks::hasAction('orders.created', $callback);
Hooks::count('orders.created');
Hooks::actions('orders.created');

$handle->remove();
```

The facade and helper also expose type-aware removal and inspection methods such as `removeAction()`, `removeFilter()`, `removeCollector()`, `removeAllActions()`, `removeAllFilters()`, and `removeAllCollectors()`.

## Singleton Lifecycle

The registry is application-singleton state. Register hooks during application bootstrapping, package bootstrapping, or another predictable setup phase.

In long-running Laravel processes such as queue workers, Octane workers, or daemons, runtime registrations remain on the singleton until the application instance is refreshed or the registrations are explicitly removed. Prefer stable boot-time registrations in those environments.

## Events, Pipeline, Or Hooks

Laravel Events are the better default for domain events, queued listeners, broadcasting, event discovery, observers, and application workflows that should integrate with Laravel's event ecosystem.

Laravel Pipeline is a better fit when one value must pass through a known middleware-like sequence.

Use this package when you need named extension points where packages or application modules can register synchronous actions, ordered filters, independent collectors, or collector result processors without introducing event classes or pipeline definitions.

## Core Package

Read the complete Hooks documentation for API details and Laravel usage:

- [hooks.momagdi.com](https://hooks.momagdi.com)
- [`magdicom/hooks`](https://github.com/magdicom/hooks)
- [`magdicom/hooks` v2.0.0-beta.2 release](https://github.com/magdicom/hooks/releases/tag/v2.0.0-beta.2)

## Testing

```bash
composer validate --strict
composer test
composer analyse
composer format -- --dry-run --diff
```

Manual smoke test:

1. Install this beta in a Laravel 12 or 13 application.
2. Register an action, filter, and collector during application boot.
3. Resolve `app(\Magdicom\Hooks::class)`, `app('hooks')`, `hooks()`, and `Hooks::getFacadeRoot()`.
4. Confirm all four access paths share the same registrations.
5. Register a non-static class callback with constructor dependencies and confirm Laravel injects them.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) and [AGENTS.md](AGENTS.md).

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mohamed Magdi](https://momagdi.com)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
