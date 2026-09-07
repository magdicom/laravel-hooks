<?php

declare(strict_types=1);

use Magdicom\LaravelHooks\Facades\Hooks;
use Magdicom\RegistrationHandle;

test('registration handles remove exact registrations', function (): void {
    $first = static fn(): string => 'first';
    $second = static fn(): string => 'second';

    $firstHandle = Hooks::addCollector('registrations.handle', $first);
    $secondHandle = hooks()->addCollector('registrations.handle', $second);

    expect($firstHandle)->toBeInstanceOf(RegistrationHandle::class)
        ->and($secondHandle)->toBeInstanceOf(RegistrationHandle::class)
        ->and(Hooks::collect('registrations.handle'))->toBe(['first', 'second']);

    expect($firstHandle->remove())->toBeTrue()
        ->and(hooks()->collect('registrations.handle'))->toBe(['second'])
        ->and($firstHandle->remove())->toBeFalse()
        ->and($secondHandle->belongsTo(facadeRoot()))->toBeTrue();
});

test('inspection and type aware removal work through facade and helper', function (): void {
    $action = static fn(): null => null;
    $filter = static function (mixed ...$arguments): string {
        [$value] = $arguments;
        if (! is_string($value)) {
            throw new InvalidArgumentException('Unexpected filter argument.');
        }

        return $value . '-filtered';
    };
    $collector = static fn(): string => 'collected';

    Hooks::addAction('registrations.typed', $action);
    hooks()->addFilter('registrations.typed', $filter);
    Hooks::addCollector('registrations.typed', $collector);

    expect(hooks()->has('registrations.typed'))->toBeTrue()
        ->and(Hooks::listeners('registrations.typed'))->toHaveCount(3)
        ->and(hooks()->actions('registrations.typed'))->toHaveCount(1)
        ->and(Hooks::filters('registrations.typed'))->toHaveCount(1)
        ->and(hooks()->collectors('registrations.typed'))->toHaveCount(1);

    expect(Hooks::removeAction('registrations.typed', $action))->toBeTrue()
        ->and(hooks()->hasAction('registrations.typed'))->toBeFalse()
        ->and(Hooks::hasFilter('registrations.typed'))->toBeTrue()
        ->and(hooks()->hasCollector('registrations.typed'))->toBeTrue();

    expect(hooks()->removeFilter('registrations.typed', $filter))->toBeTrue()
        ->and(Hooks::removeCollector('registrations.typed', $collector))->toBeTrue()
        ->and(hooks()->has('registrations.typed'))->toBeFalse();
});

test('singleton registrations are visible through every access method', function (): void {
    hooksAlias()->addCollector('registrations.singleton', static fn(): string => 'alias');

    expect(Hooks::collect('registrations.singleton'))->toBe(['alias'])
        ->and(hooks()->collectors('registrations.singleton'))->toHaveCount(1)
        ->and(app(\Magdicom\Hooks::class)->collect('registrations.singleton'))->toBe(['alias']);
});
