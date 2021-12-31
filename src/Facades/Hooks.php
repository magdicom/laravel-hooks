<?php

namespace Magdicom\LaravelHooks\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Magdicom\Hooks
 */
class Hooks extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'hooks';
    }
}
