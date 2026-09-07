<?php

declare(strict_types=1);

use Magdicom\LaravelHooks\Facades\Hooks;

test('actions execute through facade and helper with variadic arguments while ignoring returns', function (): void {
    $log = new ActionTestLog();

    Hooks::addAction('actions.facade', static function (mixed ...$arguments): string {
        [$log, $first, $second] = $arguments;
        if (! $log instanceof ActionTestLog || ! is_string($first) || ! is_string($second)) {
            throw new InvalidArgumentException('Unexpected action arguments.');
        }

        $log->push($first . ':' . $second);

        return 'ignored';
    });

    Hooks::doAction('actions.facade', $log, 'facade', 'value');

    hooks()->addAction('actions.helper', static function (mixed ...$arguments): array {
        [$log, $first, $second] = $arguments;
        if (! $log instanceof ActionTestLog || ! is_string($first) || ! is_string($second)) {
            throw new InvalidArgumentException('Unexpected action arguments.');
        }

        $log->push($first . ':' . $second);

        return ['ignored'];
    });

    hooks()->doAction('actions.helper', $log, 'helper', 'value');

    expect($log->all())->toBe([
        'facade:value',
        'helper:value',
    ]);
});

test('actions resolve class callbacks through the laravel container', function (): void {
    app()->instance(ActionTestDependency::class, new ActionTestDependency('container'));

    $log = new ActionTestLog();

    Hooks::addAction('actions.container', [ContainerActionCallback::class, 'handle']);
    hooks()->doAction('actions.container', $log, 'resolved');

    expect($log->all())->toBe(['container:resolved']);
});

final class ActionTestLog
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

final readonly class ActionTestDependency
{
    public function __construct(
        public string $value,
    ) {}
}

final readonly class ContainerActionCallback
{
    public function __construct(
        private ActionTestDependency $dependency,
    ) {}

    public function handle(ActionTestLog $log, string $value): void
    {
        $log->push($this->dependency->value . ':' . $value);
    }
}
