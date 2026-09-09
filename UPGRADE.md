# Upgrade to 2.0

`magdicom/laravel-hooks` 2.0 is intentionally breaking.

The Laravel wrapper now exposes the published `magdicom/hooks` version-2 API directly through Laravel's container, facade, and helper. Version-1 compatibility aliases and forwarding behavior are not provided.

## Requirements

- PHP `^8.2`
- Laravel `^12.0` or `^13.0`
- `magdicom/hooks` `^2.0.0-beta.2`

Laravel 9, 10, and 11 support has been removed from the version-2 branch.

## Installation

Install the beta release:

```bash
composer require magdicom/laravel-hooks:"^2.0@beta" magdicom/hooks:"^2.0@beta"
```

The core package is listed explicitly because beta consumers must permit both prerelease packages. Composer's dependency-level stability flags do not override the root project's default stable policy. Do not change your application's global `minimum-stability` setting for this package.

## Choose The Hook Type

Version 1 used one `register()` method for every behavior. Version 2 separates hook intent:

- use actions for side effects;
- use filters for sequential value transformation;
- use collectors for gathering raw results;
- use processors or renderers to finalize collector results.

## register()

Version 1:

```php
Hooks::register('invoice.paid', $callback, 10);
```

Version 2 side effect:

```php
Hooks::addAction('invoice.paid', $callback, priority: 10);
Hooks::doAction('invoice.paid', $invoiceId);
```

Version 2 transformation:

```php
Hooks::addFilter('invoice.label', $callback, priority: 10);
$label = Hooks::applyFilters('invoice.label', $label, $invoice);
```

Version 2 result gathering:

```php
Hooks::addCollector('dashboard.widgets', $callback, priority: 10);
$widgets = Hooks::collect('dashboard.widgets', $user);
```

## all()

Version 1:

```php
$output = Hooks::all('dashboard.widgets')->toArray();
```

Version 2:

```php
$output = Hooks::collect('dashboard.widgets');
```

`collect()` returns raw one-entry-per-listener results. It does not use configured processors or renderers.

## first() And last()

Version 1:

```php
$first = Hooks::first('dashboard.widgets')->toArray();
$last = Hooks::last('dashboard.widgets')->toArray();
```

Version 2:

```php
use Magdicom\Processors\FirstProcessor;
use Magdicom\Processors\LastProcessor;

Hooks::setProcessor('dashboard.first_widget', new FirstProcessor());
Hooks::setProcessor('dashboard.last_widget', new LastProcessor());

$first = Hooks::process('dashboard.first_widget');
$last = Hooks::process('dashboard.last_widget');
```

Register callbacks on the collector endpoint you process. If the same endpoint needs both first and last results, collect raw results and choose explicitly in application code, or use separate collector endpoints with separate processors.

## toArray()

Version 1:

```php
$items = Hooks::all('menu')->toArray();
```

Version 2:

```php
$items = Hooks::collect('menu');
```

There is no shared output object in version 2.

## toString()

Version 1:

```php
$html = Hooks::all('menu')->toString("\n");
```

Version 2:

```php
use Magdicom\Processors\ConcatenateRenderer;

Hooks::setRenderer('menu', new ConcatenateRenderer("\n"));

$html = Hooks::render('menu');
```

Custom renderers must implement `Magdicom\Renderer`, or you may provide a callable renderer that returns a string.

## One-Off Processing And Rendering

Use `processWith()` and `renderWith()` when a processor or renderer should apply to one call without changing the endpoint's persistent configuration:

```php
use Magdicom\Processors\LastProcessor;

$status = hooks()->processWith('invoice.status', new LastProcessor(), $invoiceId);
$html = hooks()->renderWith('invoice.summary', new InvoiceSummaryRenderer(), $invoiceId);
```

The same core methods are available through the optional facade. Class-name processors and renderers resolve through Laravel's container, including constructor dependencies.

## Global Parameter State Removed

These version-1 APIs are removed:

- `setParameter()`
- `setParam()`
- `setParameters()`
- `setParams()`
- `hooks($parameters)`

Version 2 uses explicit invocation arguments:

```php
Hooks::doAction('invoice.paid', $invoice, $user);
Hooks::applyFilters('invoice.label', $label, $invoice, $user);
Hooks::collect('dashboard.widgets', $user);
```

If several callbacks need shared state, pass a typed context object:

```php
final readonly class InvoiceHookContext
{
    public function __construct(
        public Invoice $invoice,
        public User $user,
    ) {}
}

Hooks::doAction('invoice.paid', new InvoiceHookContext($invoice, $user));
```

## Helper Behavior

Version 1 allowed helper calls that implied parameter state:

```php
hooks(['invoice' => $invoice])->all('invoice.paid');
```

Version 2:

```php
hooks()->doAction('invoice.paid', $invoice);
```

The helper now returns the shared core `Magdicom\Hooks` singleton. Passing arguments to `hooks()` throws an exception so old code does not appear to work while silently discarding parameters.

## Facade And Container

The facade resolves the same singleton as the container binding:

```php
app(\Magdicom\Hooks::class);
app('hooks');
hooks();
\Magdicom\LaravelHooks\Facades\Hooks::getFacadeRoot();
```

Registrations added through any of these paths are visible through the others.

The wrapper binds `Magdicom\Resolver` to `Magdicom\LaravelHooks\LaravelResolver` as a replaceable singleton. If an application or package needs custom class-name resolution, rebind `Magdicom\Resolver` before `Magdicom\Hooks` is first resolved.

## Dependency-Injected Class Callbacks

Non-static class callbacks now resolve through Laravel's container:

```php
final readonly class SendInvoiceReceipt
{
    public function __construct(
        private ReceiptMailer $mailer,
    ) {}

    public function handle(Invoice $invoice): void
    {
        $this->mailer->send($invoice);
    }
}

Hooks::addAction('invoice.paid', [SendInvoiceReceipt::class, 'handle']);
```

The same Laravel resolver is used for class-name processors and renderers.

## Removed Version-1 API

These APIs are not available in version 2:

- `register()`
- `all()`
- `first()`
- `last()`
- `toArray()`
- `toString()`
- `__toString()`
- `setParameter()`
- `setParam()`
- `setParameters()`
- `setParams()`

Use the core package's version-2 actions, filters, collectors, processors, renderers, inspection, and removal APIs instead.

## Further Reading

- [Hooks documentation](https://hooks.momagdi.com)
- [Hooks for Laravel documentation](https://hooks.momagdi.com/docs/2.x/laravel/)
- [`magdicom/hooks`](https://github.com/magdicom/hooks)
- [Hooks documentation](https://hooks.momagdi.com)
- [`magdicom/hooks` 2.0 upgrade guide](https://github.com/magdicom/hooks/blob/2.0/UPGRADE.md)
- [`magdicom/hooks` v2.0.0-beta.2 release](https://github.com/magdicom/hooks/releases/tag/v2.0.0-beta.2)
