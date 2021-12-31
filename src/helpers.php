<?php

use \Magdicom\LaravelHooks\Facades\Hooks;

if (! function_exists('hooks')) {
    /**
     * Wrapper for Laravel Hooks Package
     *
     * @param  array|null  $parameters
     * @return \Magdicom\LaravelHooks\Facades\Hooks
     */
    function hooks($parameters = [])
    {
        return Hooks::setParameters($parameters);
    }
}
