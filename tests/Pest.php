<?php

declare(strict_types=1);

use Magdicom\Hooks as CoreHooks;
use Magdicom\LaravelHooks\Facades\Hooks;
use Magdicom\LaravelHooks\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function hooksAlias(): CoreHooks
{
    $hooks = app('hooks');

    if (! $hooks instanceof CoreHooks) {
        throw new RuntimeException('The hooks container alias did not resolve to the core Hooks instance.');
    }

    return $hooks;
}

function facadeRoot(): CoreHooks
{
    $hooks = Hooks::getFacadeRoot();

    if (! $hooks instanceof CoreHooks) {
        throw new RuntimeException('The Hooks facade did not resolve to the core Hooks instance.');
    }

    return $hooks;
}

/**
 * @return array{providers: list<class-string>, aliases: array{Hooks: class-string}}
 */
function laravelDiscoveryMetadata(): array
{
    $decoded = json_decode((string) file_get_contents(__DIR__ . '/../composer.json'), true, flags: JSON_THROW_ON_ERROR);

    if (! is_array($decoded)) {
        throw new RuntimeException('composer.json did not decode to an array.');
    }

    $extra = $decoded['extra'] ?? null;
    if (! is_array($extra)) {
        throw new RuntimeException('composer.json is missing extra metadata.');
    }

    $laravel = $extra['laravel'] ?? null;
    if (! is_array($laravel)) {
        throw new RuntimeException('composer.json is missing Laravel discovery metadata.');
    }

    $providers = $laravel['providers'] ?? null;
    $aliases = $laravel['aliases'] ?? null;

    if (! is_array($providers) || ! is_array($aliases)) {
        throw new RuntimeException('composer.json Laravel discovery metadata is invalid.');
    }

    $providerClasses = [];
    foreach ($providers as $provider) {
        if (! is_string($provider) || ! class_exists($provider)) {
            throw new RuntimeException('composer.json contains an invalid Laravel provider class.');
        }

        $providerClasses[] = $provider;
    }

    $hooksAlias = $aliases['Hooks'] ?? null;
    if (! is_string($hooksAlias) || ! class_exists($hooksAlias)) {
        throw new RuntimeException('composer.json contains an invalid Hooks facade alias.');
    }

    return [
        'providers' => $providerClasses,
        'aliases' => [
            'Hooks' => $hooksAlias,
        ],
    ];
}
