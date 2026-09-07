<?php

declare(strict_types=1);

namespace Magdicom\LaravelHooks;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Magdicom\Hooks;
use Magdicom\Resolver;

class ServiceProvider extends LaravelServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LaravelResolver::class, static function (Container $app): LaravelResolver {
            return new LaravelResolver($app);
        });

        $this->app->singleton(Resolver::class, static function (Container $app): Resolver {
            return $app->make(LaravelResolver::class);
        });

        $this->app->singleton(Hooks::class, static function (Container $app): Hooks {
            return new Hooks($app->make(Resolver::class));
        });

        $this->app->alias(Hooks::class, 'hooks');
    }
}
