<?php

declare(strict_types=1);

use Magdicom\LaravelHooks\Facades\Hooks;
use Magdicom\MissingProcessorException;
use Magdicom\MissingRendererException;
use Magdicom\ProcessingContext;
use Magdicom\Processor\ConcatenateRenderer;
use Magdicom\Processor\LastProcessor;
use Magdicom\Renderer;
use Magdicom\ResultProcessor;

test('built in processors and renderers work through laravel access points', function (): void {
    Hooks::addCollector('processors.built-in', static fn(): string => 'first');
    hooks()->addCollector('processors.built-in', static fn(): string => 'last');
    Hooks::setProcessor('processors.built-in', new LastProcessor());

    hooks()->addCollector('renderers.built-in', static fn(): string => 'Hello');
    Hooks::addCollector('renderers.built-in', static fn(): string => 'Laravel');
    hooks()->setRenderer('renderers.built-in', new ConcatenateRenderer(' '));

    expect(hooks()->process('processors.built-in'))->toBe('last')
        ->and(Hooks::render('renderers.built-in'))->toBe('Hello Laravel');
});

test('class name processors and renderers resolve constructor dependencies through laravel', function (): void {
    app()->instance(ProcessorRendererDependency::class, new ProcessorRendererDependency('container'));

    Hooks::addCollector('processors.class-name', static function (mixed ...$arguments): string {
        [$suffix] = $arguments;
        if (! is_string($suffix)) {
            throw new InvalidArgumentException('Unexpected collector argument.');
        }

        return 'raw-' . $suffix;
    });
    hooks()->setProcessor('processors.class-name', ContainerProcessor::class);

    hooks()->addCollector('renderers.class-name', static function (mixed ...$arguments): string {
        [$suffix] = $arguments;
        if (! is_string($suffix)) {
            throw new InvalidArgumentException('Unexpected collector argument.');
        }

        return 'raw-' . $suffix;
    });
    Hooks::setRenderer('renderers.class-name', ContainerRenderer::class);

    expect(Hooks::process('processors.class-name', 'processed'))->toBe('container:processors.class-name:raw-processed')
        ->and(hooks()->render('renderers.class-name', 'rendered'))->toBe('container:renderers.class-name:raw-rendered');
});

test('collect bypasses configured processing', function (): void {
    Hooks::addCollector('processors.collect-bypass', static fn(): string => 'first');
    Hooks::addCollector('processors.collect-bypass', static fn(): string => 'second');
    Hooks::setProcessor('processors.collect-bypass', new LastProcessor());

    expect(hooks()->collect('processors.collect-bypass'))->toBe(['first', 'second'])
        ->and(Hooks::process('processors.collect-bypass'))->toBe('second');
});

test('processor renderer and listener exceptions propagate', function (): void {
    expect(static fn() => Hooks::process('processors.missing'))->toThrow(MissingProcessorException::class)
        ->and(static fn() => hooks()->render('renderers.missing'))->toThrow(MissingRendererException::class);

    Hooks::addCollector('processors.exception', static function (): string {
        throw new DomainException('collector failed');
    });
    Hooks::setProcessor('processors.exception', new LastProcessor());

    expect(static fn() => hooks()->process('processors.exception'))->toThrow(DomainException::class, 'collector failed');
});

final readonly class ProcessorRendererDependency
{
    public function __construct(
        public string $value,
    ) {}
}

/**
 * @implements ResultProcessor<mixed, string>
 */
final readonly class ContainerProcessor implements ResultProcessor
{
    public function __construct(
        private ProcessorRendererDependency $dependency,
    ) {}

    /**
     * @param list<mixed> $results
     */
    public function process(array $results, ProcessingContext $context): string
    {
        return $this->dependency->value . ':' . $context->hookPoint() . ':' . implode(',', $this->stringResults($results));
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
final readonly class ContainerRenderer implements Renderer
{
    public function __construct(
        private ProcessorRendererDependency $dependency,
    ) {}

    /**
     * @param list<mixed> $results
     */
    public function process(array $results, ProcessingContext $context): string
    {
        return $this->dependency->value . ':' . $context->hookPoint() . ':' . implode(',', $this->stringResults($results));
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
