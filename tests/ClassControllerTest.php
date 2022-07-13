<?php

namespace Tests\Unit;

use MMedia\ClassController\Http\Controllers\ClassController;
use PHPUnit\Framework\TestCase;

class ClassControllerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_that_throws_error_when_no_class_extended()
    {
        // Instantiate the ClassController class and assert an error is thrown
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('property is not defined');
        new ClassController();
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_that_throws_error_when_class_extended_doesnt_exist()
    {
        // Instantiate the ClassController class and assert an error is thrown
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('does not exist');
        new ClassController('NotFoundClass');
    }
}
