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

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test that we can add a new Route and call it using a method from the inherited class
     *
     * @return void
     */
    public function testShouldNotValidateIfNoParamsInMethod()
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

    public function testShouldNotValidateIfParamHasDefaultValue()
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

    /**
     * testEachTypedParamWithoutDefaultShouldBeRequired
     *
     * @dataProvider methodsWithParams
     * @testdox The $paramType should be validated as 'required'
     * @return void
     */
    public function testEachTypedParamWithoutDefaultShouldBeRequired($paramType)
    {
        $this->createRoute($paramType);

        $response = $this->getJson('/test-route');
        $this->assertEquals(422, $response->getStatusCode());

        $this->assertValidJsonResponseWithErrors($response, $paramType);
        $this->assertValidJsonResponseWithErrors($response, $paramType, fn ($jsonData) => $this->assertStringContainsString("is required.", $jsonData['errors']['param'][0]));

        /**
         * Calling directly (not as JSON) should return a 302 redirect with error message
         */
        $response = $this->call('GET', '/test-route');
        $this->assertRedirectWithErrors($response);
    }

    /**
     * Test that we can add a new Route and call it using a method from the inherited class
     *
     * @return void
     */
    public function testUnionTypesShouldFailWhenNoneMatched()
    {
        $this->createRoute('intOrFloatParam');

        // Call the route
        $response = $this->postJson('/test-route', ['param' => 'not an int or float']);

        // Assert the response has a 200 status code
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertValidJsonResponseWithErrors($response, 'intOrFloatParam', fn ($jsonData) => $this->assertStringContainsString("must be a type of", $jsonData['errors']['param'][0]));

        /**
         * Calling directly (not as JSON) should return a 302 redirect with success message
         */

        // Call the route
        $response = $this->call('GET', '/test-route');

        // Assert the response has a 302 status code
        $this->assertRedirectWithErrors($response);
    }

    /**
     * Test that we can add a new Route and call it using a method from the inherited class
     *
     * @return void
     */
    public function testUnionTypesShouldPassWhenMatched()
    {
        $this->createRoute('intOrFloatParam');

        // Call the route
        $response = $this->postJson('/test-route', ['param' => 1]);

        // Assert the response has a 200 status code
        $this->assertEquals(200, $response->getStatusCode());

        /**
         * Calling directly (not as JSON) should return a 302 redirect with success message
         */

        // Call the route
        $response = $this->call('GET', '/test-route');

        // Assert the response has a 302 status code
        $this->assertEquals(
            302,
            $response->getStatusCode()
        );
    }

    /**
     * Test that we can add a new Route and call it using a method from the inherited class
     *
     * @return void
     */
    public function testExceptionIsPassed()
    {
        $this->createRoute('throwsException');

        // Call the route
        $response = $this->getJson('/test-route');

        // Assert the response has a 200 status code
        $this->assertEquals(400, $response->getStatusCode());

        /**
         * Calling directly (not as JSON) should return a 302 redirect with success message
         */

        // Call the route
        $response = $this->call('GET', '/test-route');

        // Assert the response has a 302 status code
        $this->assertEquals(
            400,
            $response->getStatusCode()
        );
    }

    private function assertValidJsonResponseWithErrors($response, $paramType, callable $callback = null)
    {
        $jsonData = $response->decodeResponseJson();
        // $jsonData should contain the key "errors" and "param", for the missing param type
        $this->assertArrayHasKey('errors', $jsonData, "Missing errors key in JSON response for $paramType");
        $this->assertArrayHasKey('param', $jsonData['errors'], "Missing param key in JSON response for $paramType");
        // The key param should contain an array
        $this->assertIsArray($jsonData['errors']['param']);
        // The array should contain at least one element
        $this->assertGreaterThan(0, count($jsonData['errors']['param']));

        // Call the callback function to assert the error message
        if ($callback) {
            $callback($jsonData);
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

    public function methodsWithParams()
    {
        return [
            ['untypedParam'],
            ['mixedParam'],
            ['intParam'],
            ['intOrFloatParam'],
            ['stringParam'],
            ['boolParam'],
            ['arrayParam'],
            ['objectParam'],
            ['floatParam'],
            ['mixedVariadicParam'],
            ['intVariadicParam'],
            ['stringVariadicParam'],
            ['boolVariadicParam'],
            ['arrayVariadicParam'],
            ['objectVariadicParam'],
            ['floatVariadicParam'],
            ['stringOrNullParam'],
            // ['mixedParamWithDefaultAndVariadic'] // Special test case, that has a default param so wont show the "param" as an error
        ];
    }
}
