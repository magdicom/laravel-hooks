<?php

use Magdicom\LaravelHooks\Facades\Hooks;

test('Facade Works', function () {

    Hooks::setParameters(["Foo", "Bar", "Baz"]);

    Hooks::register("Parameters", function($vars){
        return $vars;
    });

    Hooks::all("Parameters");

    expect(Hooks::toString())->toBe("FooBarBaz");

});
