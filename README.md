# Laravel ClassController

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mmedia/classcontroller.svg?style=flat-square)](https://packagist.org/packages/mmedia/classcontroller)
[![Total Downloads](https://img.shields.io/packagist/dt/mmedia/classcontroller.svg?style=flat-square)](https://packagist.org/packages/mmedia/classcontroller)
![Maintained](https://img.shields.io/maintenance/yes/2022?style=flat-square)
[![Coverage](https://img.shields.io/badge/coverage-93%25-green.svg?style=flat-square)](https://github.com/M-Media-Group/Laravel-Class-Controller/tree/master/tests/Unit)
<!-- ![GitHub Actions](https://github.com/mmedia/classcontroller/actions/workflows/main.yml/badge.svg) -->

The ClassController extends the basic Controller and allows you to use defined PHP class methods directly as controller methods.

## Installation

You can install the package via composer:

```bash
composer require mmedia/classcontroller
```

## Usage

See the [`Test::class`](https://github.com/M-Media-Group/Laravel-Class-Controller/blob/master/examples/Test.php) that this example is using.

```php
use MMedia\ClassController\Http\Controllers\ClassController;

class TestClassController extends ClassController
{
    protected $inheritedClass = 'MMedia\ClassController\Examples\Test';

    //Done. All methods from the class Test are inherited and wrapped in Laravel validation automatically
}
```

When you extend a `ClassController` and give your new controller a name like `{inheritedClass}ClassController`, all of the methods of `{inheritedClass}` are inherited and wrapped with Laravel validation rules and responses. If you need a namespaced class, you can use [`inheritedClass`](#inheritedclass) property to specify a class with its namespace instead.

In your routes, you can now call all the methods of the inheritedClass, [`Test::class`](https://github.com/M-Media-Group/Laravel-Class-Controller/blob/master/examples/Test.php) in this case, directly:
```php
// We're just using the methods in the inherited class methods directly
Route::get('/noParams', [TestClassController::class, 'noParams']); // === \Test::noParams()
Route::get('/mixedParam/{param}', [TestClassController::class, 'mixedParam']); // === \Test::mixedParam($param) + auto validation!
```

<details><summary>Here is the equivalent when extending the default Controller instead</summary>
In a `ClassController`, all this code is auto handled for you!

```php
<?php

namespace App\Http\Controllers\Api\Test;

use Illuminate\Http\Request;

class TestController extends Controller
{
  public function noParams(Request $request)
    {
        $testClass = new Test();

        try {
            $methodResult = $testClass->noParams();
            if ($request->wantsJson()) {
                return response()->json($methodResult, 200);
            }
            return back()->with('success', $methodResult);
        } catch (\Exception $e) {
            return abort(400, $e->getMessage());
        }
    }

    public function mixedParam(Request $request)
    {
        $validatedData = $request->validate([
            'param' => ['required', 'integer'],
        ]);

        $testClass = new Test();

        try {
          $methodResult = $testClass->mixedParam($validatedData['param']);
            if ($request->wantsJson()) {
                return response()->json($methodResult, 200);
            }
            return back()->with('success', $methodResult);
        } catch (\Exception $e) {
            return abort(400, $e->getMessage());
        }
    }
}
```

</details>

## Defining parameters to pass to the inheritedClass constructor
Sometimes a class requires some parameters in its constructor. To define these parameters, your controller should implement the `classParameters` method, and return the required parameters in the correct order as an iterable.

```php
protected function classParameters(): iterable
{
    return [$param1, $param2];
}
```

## Further setting up the class after instantiation
If you need to do more on the class before a method is called but after it is set up and instantiated, you can implement the `postClassSetup` method, where you have access to the instance of your class as `$this->class()`. This method should not return anything.
```php
protected function postClassSetup(): void
{
    // Your code here. You have access to $this->class().
}
```

## Overriding a specific method
If you need to override the behaviour of a specific method, you can simply define it in your controller using the method name that you want to override. Using the original example of `Test::class`:
```php
public function mixedParam(Request $request)
{
    // You can write your own $request->validate(), or use the one from ClassController which validates that the data passed to the original class method is correct
    $validatedData = $this->getValidatedData($this->class(), 'mixedParam');

    // Call the original method if you want, or override it completely
    return $this->class()->mixedParam($validatedData['param']);
}
```
Note that while you can write your own validation logic, here we chose to use the already existing method [`getValidatedData()`](#getvalidateddataclassname-methodname-array) that is provided by the `ValidatesClassMethods` trait - the method takes a class name and method name as parameters and then validates all the required method parameters.

## Creating a ClassController with Laravel Artisan
First, make sure you publish the stubs with the following command below:

```bash
php artisan vendor:publish --tag=classcontroller-stubs
```

You can then use the Artisan command to create ClassControllers whenever you want. Specify the `---type=class` option, followed by the class name you are inheriting, to create a new ClassController:

```bash
php artisan make:controller --type=class TestClassController
```

## In detail

### Responses
The ClassController will respond differently depending on your headers. If you pass the `Accept:application/json` header, the responses will be in JSON. If not, you will be redirected back to the previous page with either a validation error if one is thrown, or a success with the result of the method called.

### Validation
Validation rules are built using the class method parameters. For example, a function `test($param1, $param2)` will build a validator that requires `param1` and `param2` to be passed in the `request`.

If the parameter has a default value, for example `test($param1 = "defaultValue")`, the validation rule for `param1` will be `nullable`.

If the parameter is typed, for example `test(int $param1)`, the validation rules for `param1` will be `required` and `integer`.

If the parameter is variadic, for example `test(...$param1)`, the validation rules for `param1` will be `required` and `array`.

If the parameter is variadic and typed, for example `test(int ...$param1)`, the validation rules for `param1` will be `required` and `array`, and each element will also have a validation rule of type `integer`.

### Exceptions

#### Validation exceptions
Validation exceptions will be handled natively by Laravel validator, and will return the errors in the `errors` key with a status code of `422`. As an example, a validation exception may return something like:
```json
{
  "errors": {
    "param": [
      "Param is required.",
      "Param must be an integer."
    ]
  }
}
```

Remember, you must pass the `accept` header in order to get JSON data back.

#### Method exceptions
If the called method throws an exception, it will be caught by the ClassController. If you have specified that you accept JSON in the response, the message will be returned as JSON with the key-value of `{"message": "Error message"}`, and a status code of `400`. Otherwise, the Laravel 400 page will be shown.

#### ClassController exceptions
Exceptions may be thrown by the ClassController itself, especially if its not set up properly. As an example: if the inheritedClass could not be determined, you will receive a native PHP error with a clear message and a status code of `500`.

## All methods

### `classParameters(): iterable`
Parameters passed to the inherited class constructor. [See: defining parameters to pass to the class](#defining-parameters-to-pass-to-the-inheritedclass-constructor).

### `postClassSetup(): void`
Method that runs after the class is instantiated. [See: further setting up the class after instantiation](#further-setting-up-the-class-after-instantiation).

### `getValidatedData($className, $methodName): array`
Method that takes the name of a method and a class, looks through the method parameters, generates a Laravel validation instance and validates it using Laravel rules. Returns an array of valid data, or throws a [`ValidationException`](#validation-exceptions) if there is a validation error. [See: validation](#validation).

### `class(): object`
Get an instance of the inherited class. The actual instance of the class, already instantiated.

## All properties
### `inheritedClass`
A protected string ('MyClass') or class (`MyClass::class`). This will be the class that the methods are inherited from.

If you do _not_ define this property and your controller follows the naming standard of `{inheritedClass}ClassController extends ClassController`, the `inheritedClass` will be taken from your controllers name.

## `ValidatesClassMethods` trait

If you want to use the validator method but don't want to extend your controller with the `ClassController` class (or want to use the validation logic outside of a controller), you can use the `ValidatesClassMethods` trait in your code.

```php

use MMedia\ClassController\ValidatesClassMethods;

class myClass
{
    use ValidatesClassMethods;

  /**
   * Example method showing how to use the `getValidatedData` method.
   *
   * @param string $param1
   * @param int|null $param2
   * @throws ValidationException
   * @return string
   */
  public function test(string $param1, ?int $param2)
  {
      // Note: in the real world, it makes little sense to get validated data from within the same method as the method that is being called - PHP would have already thrown an error if the params were not valid types.
      $validatedData = $this->getValidatedData(get_class($this), __FUNCTION__);

      // Your code here. $validatedData['param1'] is a valid string and $validatedData['param2'] is a valid integer or null.

      return $validatedData['param1'] . $validatedData['param2'];
  }
}
```

## Limitations
- Untyped method parameters will not generate a specific type validation rule - [see: Validation](#validation)
- Method parameters of type array will not validate each array element
- Methods that don't specify parameters but instead use `func_get_args()` will not generate any validation rules
- Authorisation is not implemented - however, you can authorise requests at the [route level](https://laravel.com/docs/authorization#via-middleware)

## Package development

### Running

You can use the included VSCode devcontainer to develop within a PHP container.

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email contact@mmediagroup.fr instead of using the issue tracker.

## Credits

-   [M Media](https://github.com/mmedia)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
