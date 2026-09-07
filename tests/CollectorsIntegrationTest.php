<?php

declare(strict_types=1);

use Magdicom\LaravelHooks\Facades\Hooks;

test('collectors return raw one entry per listener and empty results with no listeners', function (): void {
    expect(hooks()->collect('collectors.empty'))->toBe([]);

    Hooks::addCollector('collectors.raw', static function (mixed ...$arguments): string {
        [$value] = $arguments;
        if (! is_string($value)) {
            throw new InvalidArgumentException('Unexpected collector argument.');
        }

        return 'second-' . $value;
    }, priority: 20);
    hooks()->addCollector('collectors.raw', static function (mixed ...$arguments): string {
        [$value] = $arguments;
        if (! is_string($value)) {
            throw new InvalidArgumentException('Unexpected collector argument.');
        }

        return 'first-' . $value;
    }, priority: 5);

    expect(Hooks::collect('collectors.raw', 'value'))->toBe([
        'first-value',
        'second-value',
    ]);
});

test('collectors accept variadic arguments and resolve class callbacks through the laravel container', function (): void {
    app()->instance(CollectorTestDependency::class, new CollectorTestDependency('container'));

    Hooks::addCollector('collectors.container', [ContainerCollectorCallback::class, 'handle']);

    expect(hooks()->collect('collectors.container', 'first', 'second'))->toBe([
        'container:first:second',
    ]);
});

final readonly class CollectorTestDependency
{
    public function __construct(
        public string $value,
    ) {}
}

final readonly class ContainerCollectorCallback
{
    public function __construct(
        private CollectorTestDependency $dependency,
    ) {}

    public function handle(string $first, string $second): string
    {
        return $this->dependency->value . ':' . $first . ':' . $second;
    }
}
