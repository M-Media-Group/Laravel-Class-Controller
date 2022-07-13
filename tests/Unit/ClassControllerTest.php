<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use MMedia\ClassController\Http\Controllers\ClassController;
use Orchestra\Testbench\TestCase;

/**
 *
 * @covers MMedia\ClassController\Http\Controllers\ClassController
 */
class ClassControllerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Route::any('test-route', ['as' => 'test-route']);
    }

    /**
     * A basic test example.
     *
     * @coversNothing
     * @return void
     */
    public function test_that_true_is_true()
    {
        $this->assertTrue(true);
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

    /**
     * Test that the class can be instantiated with a valid class name.
     *
     * @return void
     */
    public function test_that_class_can_be_instantiated_with_valid_class_name()
    {
        // Current class FQN
        $classFQN = get_class($this);

        // Instantiate the ClassController class and assert no error is thrown
        $this->assertInstanceOf(ClassController::class, new ClassController($classFQN));
    }

    /**
     * Test that we can get an instance of the class using the protected class() method
     *
     * @return void
     */
    public function test_that_we_can_get_an_instance_of_the_class_using_the_protected_class_method()
    {
        // Current class FQN
        $classFQN = get_class($this);

        // Instantiate the ClassController class and assert no error is thrown
        $classController = new ClassController($classFQN);

        // Get the class instance
        $class = $classController->class();

        // Assert that the class is an instance of the current class
        $this->assertInstanceOf($classFQN, $class);
    }

    /**
     * Test that we can add a new Route and call it using a method from the inherited class
     *
     * @return void
     */
    public function test_that_we_can_add_a_new_route_and_call_it_using_a_method_from_the_inherited_class()
    {
        // Current class FQN
        $classFQN = get_class($this);

        // Instantiate the ClassController class and assert no error is thrown
        $classController = new ClassController($classFQN);

        // Function name to test
        $functionName = 'test_that_true_is_true';

        // Add a new route to test with, using the $classController callback
        Route::any('test-route', ['as' => 'test-route'])->name('test-route')->uses(function () use ($classController, $functionName) {
            // Call the method to test
            return $classController->$functionName();
        });

        // Assert the route exists
        $this->assertTrue(Route::has('test-route'));

        // Call the route
        $response = $this->getJson('/test-route');

        // Assert the response has a 200 status code
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test that we can add a new Route and call it using a method from the inherited class
     *
     * @return void
     */
    public function test_that_we_cannot_call_a_nonexistent_method_from_the_inherited_class()
    {
        // Current class FQN
        $classFQN = get_class($this);

        // Instantiate the ClassController class and assert no error is thrown
        $classController = new ClassController($classFQN);

        // Function name to test
        $functionName = 'thisMethodDoesNotExist';

        // Add a new route to test with, using the $classController callback
        Route::any('test-route', ['as' => 'test-route'])->name('test-route')->uses(function () use ($classController, $functionName) {
            // Call the method to test
            return $classController->$functionName();
        });

        // Assert the route exists
        $this->assertTrue(Route::has('test-route'));

        // Call the route
        $response = $this->getJson('/test-route');

        // Assert the response has a 500 status code since the called method doesnt exist
        $this->assertEquals(500, $response->getStatusCode());
    }
}
