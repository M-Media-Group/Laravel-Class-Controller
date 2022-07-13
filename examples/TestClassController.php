<?php

namespace MMedia\ClassController\Examples;

use MMedia\ClassController\Http\Controllers\ClassController;

class TestClassController extends ClassController
{
    protected $inheritedClass = 'MMedia\ClassController\Examples\Test';
    //   Inherits the methods from Test.php automatically
}
