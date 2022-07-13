<?php

namespace MMedia\ClassController\Examples;

use MMedia\ClassController\Http\Controllers\ClassController;

/**
 * A sample class controller that extends ClassController. Also used in the tests.
 */
class TestClassController extends ClassController
{
    /**
     * Even though we named our controller TestClassController, we set the inheritedClass property here because it is namespaced.
     *
     * @var string
     */
    protected $inheritedClass = 'MMedia\ClassController\Examples\Test';

    //Done. All methods from Test.php are inherited and wrapped in Laravel validation automatically
}
