<?php

declare(strict_types=1);

use Magdicom\LaravelHooks\Facades\Hooks;
use Magdicom\Processors\ConcatenateRenderer;
use Magdicom\Processors\FirstProcessor;
use Magdicom\RegistrationHandle;

test('facade exposes action filter collector processor and renderer APIs', function (): void {
    $events = new PublicApiEventLog();

    $actionHandle = Hooks::addAction(
        'facade.action',
        static function (mixed ...$arguments): string {
            [$events, $value] = $arguments;
            if (! $events instanceof PublicApiEventLog || ! is_string($value)) {
                throw new InvalidArgumentException('Unexpected action arguments.');
            }

            $events->push('action:' . $value);

            return 'ignored';
        },
    );

    Hooks::doAction('facade.action', $events, 'one');

    Hooks::addFilter('facade.filter', static function (mixed ...$arguments): string {
        [$value] = $arguments;
        if (! is_string($value)) {
            throw new InvalidArgumentException('Unexpected filter argument.');
        }

        return $value . '-second';
    }, priority: 20);
    Hooks::addFilter('facade.filter', static function (mixed ...$arguments): string {
        [$value] = $arguments;
        if (! is_string($value)) {
            throw new InvalidArgumentException('Unexpected filter argument.');
        }

        return $value . '-first';
    }, priority: 5);

    Hooks::addCollector('facade.collector', static function (mixed ...$arguments): string {
        [$value] = $arguments;
        if (! is_string($value)) {
            throw new InvalidArgumentException('Unexpected collector argument.');
        }

        return 'second-' . $value;
    }, priority: 20);
    Hooks::addCollector('facade.collector', static function (mixed ...$arguments): string {
        [$value] = $arguments;
        if (! is_string($value)) {
            throw new InvalidArgumentException('Unexpected collector argument.');
        }

        return 'first-' . $value;
    }, priority: 5);

    Hooks::addCollector('facade.processor', static fn(): string => 'first');
    Hooks::addCollector('facade.processor', static fn(): string => 'second');
    Hooks::setProcessor('facade.processor', new FirstProcessor());

    Hooks::addCollector('facade.renderer', static fn(): string => 'Hello');
    Hooks::addCollector('facade.renderer', static fn(): string => 'Laravel');
    Hooks::setRenderer('facade.renderer', new ConcatenateRenderer(' '));

    expect($actionHandle)->toBeInstanceOf(RegistrationHandle::class)
        ->and($events->all())->toBe(['action:one'])
        ->and(Hooks::applyFilters('facade.filter', 'value'))->toBe('value-first-second')
        ->and(Hooks::collect('facade.collector', 'value'))->toBe(['first-value', 'second-value'])
        ->and(Hooks::process('facade.processor'))->toBe('first')
        ->and(Hooks::render('facade.renderer'))->toBe('Hello Laravel');
});

test('helper exposes the shared core instance without parameter state', function (): void {
    hooks()->addAction('helper.action', static function (mixed ...$arguments): null {
        [$events, $value] = $arguments;
        if (! $events instanceof PublicApiEventLog || ! is_string($value)) {
            throw new InvalidArgumentException('Unexpected action arguments.');
        }

        return $events->push($value);
    });
    hooks()->addFilter('helper.filter', static function (mixed ...$arguments): string {
        [$value, $suffix] = $arguments;
        if (! is_string($value) || ! is_string($suffix)) {
            throw new InvalidArgumentException('Unexpected filter arguments.');
        }

        return $value . '-' . $suffix;
    });
    hooks()->addCollector('helper.collector', static function (mixed ...$arguments): string {
        [$value, $suffix] = $arguments;
        if (! is_string($value) || ! is_string($suffix)) {
            throw new InvalidArgumentException('Unexpected collector arguments.');
        }

        return $value . '-' . $suffix;
    });

    $events = new PublicApiEventLog();
    hooks()->doAction('helper.action', $events, 'explicit');

    expect(hooks())->toBe(facadeRoot())
        ->and($events->all())->toBe(['explicit'])
        ->and(hooks()->applyFilters('helper.filter', 'value', 'argument'))->toBe('value-argument')
        ->and(hooks()->collect('helper.collector', 'value', 'argument'))->toBe(['value-argument']);
});

test('facade and helper share registration inspection and removal', function (): void {
    $callback = static fn(): string => 'registered';
    $handle = Hooks::addCollector('registration.shared', $callback);

    expect($handle)->toBeInstanceOf(RegistrationHandle::class)
        ->and(hooks()->has('registration.shared'))->toBeTrue()
        ->and(hooks()->hasCollector('registration.shared', $callback))->toBeTrue()
        ->and(Hooks::collectors('registration.shared'))->toHaveCount(1)
        ->and($handle->belongsTo(hooks()))->toBeTrue();

    expect($handle->remove())->toBeTrue()
        ->and(Hooks::hasCollector('registration.shared', $callback))->toBeFalse();

    Hooks::addAction('registration.remove-action', static fn(): null => null);

    expect(hooks()->hasAction('registration.remove-action'))->toBeTrue()
        ->and(hooks()->removeAllActions('registration.remove-action'))->toBe(1)
        ->and(Hooks::hasAction('registration.remove-action'))->toBeFalse();
});

final class PublicApiEventLog
{
    /**
     * @var list<string>
     */
    private array $events = [];

    public function push(string $event): null
    {
        $this->events[] = $event;

        return null;
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return $this->events;
    }
}
