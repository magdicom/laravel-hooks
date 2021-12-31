# A lightweight action hooks package for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/magdicom/laravel-hooks.svg?style=flat-square)](https://packagist.org/packages/magdicom/laravel-hooks)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/magdicom/laravel-hooks/run-tests?label=tests)](https://github.com/magdicom/laravel-hooks/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/magdicom/laravel-hooks/Check%20&%20fix%20styling?label=code%20style)](https://github.com/magdicom/laravel-hooks/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/magdicom/laravel-hooks.svg?style=flat-square)](https://packagist.org/packages/magdicom/laravel-hooks)
 
This is a laravel 8 [magdicom/hooks](https://github.com/magdicom/hooks) wrapper that lets easily use action hooks in your laravel project.


## Installation

You can install the package via composer:

```bash
composer require magdicom/laravel-hooks
```

## Usage

To call any of the available method you can use the facade:

```php
use Momagdi\LaravelHooks\Facade\Hooks;

Hooks::register("HookName", function($vars){}, 1);
```

or use the helper function as below:
```php
hooks()->register("HookName", function($vars){}, 1);
```

<a name="quick-start"></a>
### Quick Start
```php
# Register our functions
Hooks::register("Greetings", function($vars){
    return "Hi There,";
}, 1);

Hooks::register("Greetings", function($vars){
    return "This is the second line of greetings!";
}, 2);

# Later we run it
echo Hooks::all("Greetings")->toString("<br>");
```
The above example will output
```text
Hi There,
This is the second line of greetings!
```

<a name="output"></a>
### Output
When you call any of [`all`](#methods-all), [`first`](#methods-first) or [`last`](#methods-last) methods, the corresponding hook functions will be executed and their output will be saved in a special property to be exported later using [`toString`](#methods-tostring) or [`toArray`](#methods-toarray) methods.

<a name="callbacks"></a>
### Callbacks

<a name="callbacks-closure"></a>
#### Closure
```php
Hooks::register("Callback", function($vars) {
    return "Closure";
});
```

<a name="callbacks-function-name"></a>
#### Function Name
```php
function simple_function_name($vars){
    //
}

Hooks::register("Callback", "simple_function_name");
```

<a name="callbacks-object-method"></a>
#### Object Method
```php
class FooBar {
    public function methodName($vars){
        //
    }
}

$object = new FooBar;

Hooks::register("Callback", [$object, 'methodName']);
```
or
```php
Hooks::register("Callback", [(new FooBar), 'methodName']);
```

<a name="callbacks-static-method"></a>
#### Static Method
```php
class FooBar {
    public static function staticMethodName($vars){
        //
    }
}

Hooks::register("Callback", ['FooBar', 'staticMethodName']);
```
in case this is not a static method, an object will be created and the provided method will be called.

<a name="parameters"></a>
### Parameters
With each of hook callback functions execution an array of parameters could be passed to it to help it perform the required action.

Parameters split into two types:
+ Global parameters will be available across all hook names and callbacks, and these can be defined using [`setParameter`](#methods-setparameter) and [`setParameters`](#methods-setparameters) methods.
+ Scoped parameters which will be only available to the requested hook name, and could be provided as the second argument of [`all`](#methods-all), [`first`](#methods-first) and [`last`](#methods-last) methods.


<a name="priority"></a>
### Priority
When you need to ensure that certain hook functions should be executed in sequence order, here it comes `$priority` which is the 3rd and last argument of [`register`](#methods-register) method.

<a name="methods"></a>
### Methods

<a name="methods-register"></a>
#### register
```php
Hooks::register(string $hookName, array|callable $callback, ?int $priority): self
```
Register all your hook functions via this method:

+ `$hookName` this can be anything you want, its like a group name where all other related action hook functions will be attached to.
+ `$callback` only accepts [callable](https://www.php.net/manual/en/language.types.callable.php) functions.
+ `$priority` (optional) used to sort callbacks before being executed.

<a name="methods-all"></a>
#### all
```php
Hooks::all(string $hookName, ?array $parameters): self
```
Will execute all callback functions of the specified hook name, by default it will return the output as string, check [output](#output) section for more options.
+ `$hookName` the hook name you want to execute its callback functions.
+ `$parameters` optional key, value pair array that you want to provide for all callback functions related to the same hook name.

Please Note: parameters provided via this method will be available only in the scope of the specified hook name, to specify global parameters use [`setParameter`](#methods-setparameter), [`setParameters`](#methods-setparameters) methods instead.

<a name="methods-first"></a>
#### first
```php
Hooks::first(string $hookName, ?array $parameters): self
```
Similar to [`all`](#methods-all) method in every aspect with the exception that only the first callback (after sorting) will be executed.

<a name="methods-last"></a>
#### last
```php
Hooks::last(string $hookName, ?array $parameters): self
```
Similar to [`all`](#methods-all) method in every aspect with the exception that only the last callback (after sorting) will be executed.

<a name="methods-toarray"></a>
#### toArray
```php
Hooks::toArray(): array
```
Will return output of the last executed hook name functions as an array.

<a name="methods-tostring"></a>
#### toString
```php
Hooks::toString(?string $separator): string
```
Will return output of the last executed hook name functions as one string.
+ `$separator` could be used to separate the output as you need (e.g: "\n", "&lt;br&gt;").

<a name="methods-setparameter"></a>
#### setParameter
```php
Hooks::setParameter(string $name, mixed $value): self
```
Use this method to define a parameter that will be accessible from any hook function.
+ `$name` name of the parameter.
+ `$value` value of the parameter could be string, array or even an object.

P.S: if the parameter already defined then its old value will be replaced by the value provided here.

<a name="methods-setparameters"></a>
#### setParameters
```php
Hooks::setParameters(array $parameters): self
```
Same as [`setParameter`](#methods-setparameter) but here it accepts a name, value pair array as its only argument.



## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mohamed Magdi](https://github.com/magdicom)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
