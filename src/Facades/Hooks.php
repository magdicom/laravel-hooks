<?php

namespace Magdicom\LaravelHooks\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Magdicom\Hooks
 */
class Hooks extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \Magdicom\Hooks::class;
    }
}
