<?php

declare(strict_types=1);

use Magdicom\Hooks as CoreHooks;
use Magdicom\LaravelHooks\Facades\Hooks;
use Magdicom\LaravelHooks\LaravelResolver;
use Magdicom\ProcessingContext;
use Magdicom\Renderer;
use Magdicom\Resolver;
use Magdicom\ResultProcessor;

test('package metadata advertises laravel auto discovery', function (): void {
    $laravel = laravelDiscoveryMetadata();

    expect($laravel['providers'])->toBe([Magdicom\LaravelHooks\ServiceProvider::class])
        ->and($laravel['aliases']['Hooks'])->toBe(Hooks::class);
});

test('service provider registers resolver and shared hooks singleton access paths', function (): void {
    $core = app(CoreHooks::class);

    expect(app(LaravelResolver::class))->toBeInstanceOf(LaravelResolver::class)
        ->and(app(Resolver::class))->toBe(app(LaravelResolver::class))
        ->and(hooksAlias())->toBe($core)
        ->and(hooks())->toBe($core)
        ->and(facadeRoot())->toBe($core);

    Hooks::addAction('laravel.singleton', static function (mixed ...$arguments): void {
        [$events] = $arguments;
        if (! $events instanceof EventLog) {
            throw new InvalidArgumentException('Unexpected action argument.');
        }

        $events->push('facade');
    });

    $events = new EventLog();
    hooks()->doAction('laravel.singleton', $events);

    expect($events->all())->toBe(['facade'])
        ->and(hooksAlias()->actions('laravel.singleton'))->toHaveCount(1);
});

test('helper rejects legacy global parameters', function (): void {
    hooks(['legacy' => 'parameters']);
})->throws(InvalidArgumentException::class, 'hooks() no longer accepts global parameters.');

test('laravel resolver uses container injection for callback classes', function (): void {
    app()->instance(ContainerBackedDependency::class, new ContainerBackedDependency('container'));

    Hooks::addAction('laravel.action', [ContainerBackedAction::class, 'handle']);
    Hooks::addFilter('laravel.filter', [ContainerBackedFilter::class, 'handle']);
    Hooks::addCollector('laravel.collector', [ContainerBackedCollector::class, 'handle']);

    $events = new EventLog();
    Hooks::doAction('laravel.action', $events, 'action');

    expect($events->all())->toBe(['container:action'])
        ->and(Hooks::applyFilters('laravel.filter', 'value', 'filter'))->toBe('value-container-filter')
        ->and(Hooks::collect('laravel.collector', 'collector'))->toBe(['container:collector']);
});

test('class name processors and renderers are resolved through laravel container', function (): void {
    app()->instance(ContainerBackedDependency::class, new ContainerBackedDependency('container'));

    hooks()->addCollector('laravel.processor', static function (mixed ...$arguments): string {
        [$suffix] = $arguments;
        if (! is_string($suffix)) {
            throw new InvalidArgumentException('Unexpected collector argument.');
        }

        return 'first-' . $suffix;
    });
    hooks()->addCollector('laravel.processor', static function (mixed ...$arguments): string {
        [$suffix] = $arguments;
        if (! is_string($suffix)) {
            throw new InvalidArgumentException('Unexpected collector argument.');
        }

        return 'second-' . $suffix;
    });
    hooks()->setProcessor('laravel.processor', ContainerBackedProcessor::class);

    expect(hooks()->collect('laravel.processor', 'raw'))->toBe(['first-raw', 'second-raw'])
        ->and(hooks()->process('laravel.processor', 'processed'))->toBe('container:first-processed|second-processed');

    Hooks::addCollector('laravel.renderer', static function (mixed ...$arguments): string {
        [$suffix] = $arguments;
        if (! is_string($suffix)) {
            throw new InvalidArgumentException('Unexpected collector argument.');
        }

        return 'rendered-' . $suffix;
    });
    Hooks::setRenderer('laravel.renderer', ContainerBackedRenderer::class);

    expect(Hooks::render('laravel.renderer', 'html'))->toBe('container:<rendered-html>');
});

test('static callable methods are not resolved through the container', function (): void {
    Hooks::addAction('laravel.static', [StaticOnlyAction::class, 'handle']);
    Hooks::addFilter('laravel.static-filter', [StaticOnlyFilter::class, 'handle']);
    Hooks::addCollector('laravel.static-collector', [StaticOnlyCollector::class, 'handle']);

    $events = new EventLog();
    Hooks::doAction('laravel.static', $events);

    expect($events->all())->toBe(['static'])
        ->and(Hooks::applyFilters('laravel.static-filter', 'value'))->toBe('value-static')
        ->and(Hooks::collect('laravel.static-collector'))->toBe(['static']);
});

final class EventLog
{
    /**
     * @var list<string>
     */
    private array $events = [];

    public function push(string $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return $this->events;
    }
}

final readonly class ContainerBackedDependency
{
    public function __construct(
        public string $value,
    ) {}
}

final readonly class ContainerBackedAction
{
    public function __construct(
        private ContainerBackedDependency $dependency,
    ) {}

    public function handle(EventLog $events, string $event): void
    {
        $events->push($this->dependency->value . ':' . $event);
    }
}

final readonly class ContainerBackedFilter
{
    public function __construct(
        private ContainerBackedDependency $dependency,
    ) {}

    public function handle(string $value, string $event): string
    {
        return $value . '-' . $this->dependency->value . '-' . $event;
    }
}

final readonly class ContainerBackedCollector
{
    public function __construct(
        private ContainerBackedDependency $dependency,
    ) {}

    public function handle(string $event): string
    {
        return $this->dependency->value . ':' . $event;
    }
}

/**
 * @implements ResultProcessor<mixed, string>
 */
final readonly class ContainerBackedProcessor implements ResultProcessor
{
    public function __construct(
        private ContainerBackedDependency $dependency,
    ) {}

    /**
     * @param list<mixed> $results
     */
    public function process(array $results, ProcessingContext $context): string
    {
        return $this->dependency->value . ':' . implode('|', $this->stringResults($results));
    }

    /**
     * @param list<mixed> $results
     * @return list<string>
     */
    private function stringResults(array $results): array
    {
        return array_map(static function (mixed $result): string {
            if (! is_string($result)) {
                throw new InvalidArgumentException('Unexpected processor result.');
            }

            return $result;
        }, $results);
    }
}

/**
 * @implements Renderer<mixed>
 */
final readonly class ContainerBackedRenderer implements Renderer
{
    public function __construct(
        private ContainerBackedDependency $dependency,
    ) {}

    /**
     * @param list<mixed> $results
     */
    public function process(array $results, ProcessingContext $context): string
    {
        return $this->dependency->value . ':<' . implode('', $this->stringResults($results)) . '>';
    }

    /**
     * @param list<mixed> $results
     * @return list<string>
     */
    private function stringResults(array $results): array
    {
        return array_map(static function (mixed $result): string {
            if (! is_string($result)) {
                throw new InvalidArgumentException('Unexpected renderer result.');
            }

            return $result;
        }, $results);
    }
}

final class StaticOnlyAction
{
    public function __construct()
    {
        throw new RuntimeException('Static action should not be resolved.');
    }

    public static function handle(EventLog $events): void
    {
        $events->push('static');
    }
}

final class StaticOnlyFilter
{
    public function __construct()
    {
        throw new RuntimeException('Static filter should not be resolved.');
    }

    public static function handle(string $value): string
    {
        return $value . '-static';
    }
}

final class StaticOnlyCollector
{
    public function __construct()
    {
        throw new RuntimeException('Static collector should not be resolved.');
    }

    public static function handle(): string
    {
        return 'static';
    }
}
