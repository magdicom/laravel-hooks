<?php

declare(strict_types=1);

namespace Magdicom\LaravelHooks;

use Illuminate\Contracts\Container\Container;
use Magdicom\Resolver;
use RuntimeException;

final readonly class LaravelResolver implements Resolver
{
    public function __construct(
        private Container $container,
    ) {}

    public function resolve(string $className): object
    {
        $resolved = $this->container->make($className);

        if (! is_object($resolved)) {
            throw new RuntimeException(sprintf(
                'Laravel container resolved [%s] to [%s]; expected object.',
                $className,
                get_debug_type($resolved),
            ));
        }

        return $resolved;
    }
}
