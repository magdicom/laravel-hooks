<?php

declare(strict_types=1);

use Magdicom\LaravelHooks\Facades\Hooks;

test('filters return original value with no listeners', function (): void {
    expect(hooks()->applyFilters('filters.empty', 'original'))->toBe('original');
});

test('filters transform sequentially with priorities and variadic arguments', function (): void {
    Hooks::addFilter(
        'filters.sequence',
        static function (mixed ...$arguments): string {
            [$value, $suffix] = $arguments;
            if (! is_string($value) || ! is_string($suffix)) {
                throw new InvalidArgumentException('Unexpected filter arguments.');
            }

            return $value . '-late-' . $suffix;
        },
        priority: 20,
    );

    hooks()->addFilter(
        'filters.sequence',
        static function (mixed ...$arguments): string {
            [$value, $suffix] = $arguments;
            if (! is_string($value) || ! is_string($suffix)) {
                throw new InvalidArgumentException('Unexpected filter arguments.');
            }

            return $value . '-early-' . $suffix;
        },
        priority: 5,
    );

    expect(Hooks::applyFilters('filters.sequence', 'start', 'arg'))->toBe('start-early-arg-late-arg');
});

test('filters resolve class callbacks through the laravel container', function (): void {
    app()->instance(FilterTestDependency::class, new FilterTestDependency('container'));

    Hooks::addFilter('filters.container', [ContainerFilterCallback::class, 'handle']);

    expect(hooks()->applyFilters('filters.container', 'value', 'argument'))->toBe('value-container-argument');
});

final readonly class FilterTestDependency
{
    public function __construct(
        public string $value,
    ) {}
}

final readonly class ContainerFilterCallback
{
    public function __construct(
        private FilterTestDependency $dependency,
    ) {}

    public function handle(string $value, string $argument): string
    {
        return $value . '-' . $this->dependency->value . '-' . $argument;
    }
}
