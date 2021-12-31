<?php

namespace Magdicom\LaravelHooks;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

use Magdicom\Hooks;

class ServiceProvider extends LaravelServiceProvider
{
    public function register()
    {
        $this->app->bind('hooks', function () {
            return new Hooks();
        });
    }
}
