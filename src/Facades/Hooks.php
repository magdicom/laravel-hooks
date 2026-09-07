<?php

declare(strict_types=1);

namespace Magdicom\LaravelHooks\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Magdicom\Hooks
 */
class Hooks extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Magdicom\Hooks::class;
    }
}
