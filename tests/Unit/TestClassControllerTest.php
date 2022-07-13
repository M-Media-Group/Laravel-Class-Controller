<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use MMedia\ClassController\Examples\TestClassController;
use Orchestra\Testbench\TestCase;

/**
 *
 * @covers MMedia\ClassController\Http\Controllers\ClassController
 */
class TestClassControllerTest extends TestCase
{

    private $classControllerNamespace = "MMedia\ClassController\Examples\TestClassController";
    private $classController;

    public function setUp(): void
    {
        parent::setUp();
        // Route::any('test-route', ['as' => 'test-route']);
        $this->classController = new TestClassController();
    }


    /**
     * Test that we can add a new Route and call it using a method from the inherited class
     *
     * @return void
     */
    public function test_method_noParams()
    {
        $this->createRoute('noParams');

        // Call the route
        $response = $this->getJson('/test-route');

        // Assert the response has a 200 status code
        $this->assertEquals(200, $response->getStatusCode());

        /**
         * Calling directly (not as JSON) should return a 302 redirect with success message
         */

        // Call the route
        $response = $this->call('GET', '/test-route');

        // Assert the response has a 200 status code
        $this->assertEquals(
            302,
            $response->getStatusCode()
        );
    }

    public function test_method_mixedParamWithDefault()
    {
        $this->createRoute('mixedParamWithDefault');

        // Call the route
        $response = $this->getJson('/test-route');

        // Assert the response has a 200 status code
        $this->assertEquals(200, $response->getStatusCode());

        /**
         * Calling directly (not as JSON) should return a 302 redirect with success message
         */

        // Call the route
        $response = $this->call('GET', '/test-route');

        // Assert the response has a 200 status code
        $this->assertEquals(
            302,
            $response->getStatusCode()
        );
    }

    public function testEachParamType()
    {
        $paramTypes = [
            'mixedParam',
            'intParam',
            'stringParam',
            'boolParam',
            'arrayParam',
            'objectParam',
            'floatParam',
            'mixedVariadicParam',
            'intVariadicParam',
            'stringVariadicParam',
            'boolVariadicParam',
            'arrayVariadicParam',
            'objectVariadicParam',
            'floatVariadicParam',
            'mixedParamWithDefaultAndVariadic'
        ];
        foreach ($paramTypes as $paramType) {
            $this->createRoute($paramType);
            $response = $this->getJson('/test-route');
            $this->assertEquals(422, $response->getStatusCode());

            /**
             * Calling directly (not as JSON) should return a 302 redirect with success message
             */
            $response = $this->call('GET', '/test-route');
            $this->assertRedirectWithErrors($response);
        }
    }


    private function assertRedirectWithErrors($response)
    {
        // Assert the response has a 200 status code
        $this->assertEquals(
            302,
            $response->getStatusCode()
        );
        // Assert that the Session has errors
        $this->assertSessionHasErrors($response);
    }

    private function assertSessionHasErrors($response)
    {
        $this->assertArrayHasKey('errors', $response->getSession()->all());
    }

    private function createRoute($method)
    {
        // Add a new route to test with, using the $classController callback
        Route::any('test-route', ['as' => 'test-route'])->name('test-route')->uses($this->classControllerNamespace . '@' . $method);

        // Assert the route exists
        $this->assertTrue(Route::has('test-route'));
    }
}
