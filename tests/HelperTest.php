<?php

test('Helper Function Works', function () {
    hooks(["Foo", "Bar", "Baz"])
    ->register("Parameters", function ($vars) {
        return $vars;
    });

    hooks()->all("Parameters");

    expect(hooks()->toString())->toBe("FooBarBaz");
});
