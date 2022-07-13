<?php

namespace MMedia\ClassController\Http\Controllers;

// use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

class ClassController extends Controller
{

    /**
     * A class name
     *
     * @var string
     */
    protected $inheritedClass;

    /**
     * An instance of a class
     *
     * @var object
     */
    private $classInstance;

    /**
     * The end of a controller name that will be used to determine if the controller is inheriting a class
     *
     * @var string
     */
    private $classControllerName = "ClassController";

    /**
     * Constructor
     */
    public function __construct(?string $inheritedClass = null)
    {
        if ($inheritedClass) {
            $this->inheritedClass = $inheritedClass;
        }
        $this->setupClass();
    }

    /**
     * Setup the class instance.
     *
     * @throws Exception if the class that is defined in the $inheritedClass does not exist
     * @return void
     */
    private function setupClass(): void
    {
        if (!isset($this->inheritedClass) && !$this->inheritedClass = $this->getNameFromParentClassName()) {
            throw new \Exception('The $inheritedClass property is not defined. You can either define the property or end your class/controller name with ' . $this->classControllerName);
        }

        if (!class_exists($this->inheritedClass)) {
            throw new \Exception('The class ' . $this->inheritedClass . ' does not exist.');
        }

        $this->validateConstructorParameters($this->inheritedClass, $this->classParameters());

        $this->classInstance = new $this->inheritedClass(
            ...$this->classParameters(),
        );

        $this->postClassSetup();
    }

    /**
     * Get the class parameters. This method is used to get the class parameters for the class instance.
     *
     * @return iterable an iterable of parameters to be used in the class constructor, or an empty array if no parameters are needed
     */
    protected function classParameters(): iterable
    {
        return [];
    }

    /**
     * Additional setup in the constructor for the current $this->class(). If you need to do additional operations on the class to set it up, you should over-write this method and do your own logic here
     *
     * @return void
     */
    protected function postClassSetup(): void
    {
        //
    }

    /**
     * Catch and route all calls to methods() that are not defined in this controller.
     *
     * @param string $method
     * @param array|null $parameters - currently unused, the passed parameters are taken from the request() object
     * @throws Exception if the method does not exist in the class
     * @return \Illuminate\Http\JsonResponse
     */
    public function __call($method, $parameters)
    {
        if (!method_exists($this->inheritedClass, $method)) {
            throw new \Exception('Method ' . $method . ' does not exist in class ' . $this->inheritedClass);
        }

        $data = $this->getValidatedData($method);

        try {
            $methodResult = call_user_func_array([$this->class(), $method], $data);

            if (request()->wantsJson()) {
                return response()->json($methodResult, 200);
            }
            return back()->with('success', $methodResult);
        } catch (\Exception $e) {
            return abort(400, $e->getMessage());
        }
    }

    /**
     * Validate the parameters that the class takes
     *
     * @param object|string $class
     * @param iterable $setParameters
     * @return void
     */
    private function validateConstructorParameters($class, iterable $setParameters): void
    {
        if (!method_exists($class, '__construct')) {
            return;
        }

        // Get the parameters that the constructor takes
        $reflection = new \ReflectionMethod($class, '__construct');
        $parameters = $reflection->getParameters();

        $i = 0;

        // If one of the parameters are not in classParameters(), throw an exception
        foreach ($parameters as $parameter) {
            // If the parameter does not have a default value, check that it is in classParameters()
            if (!$parameter->isDefaultValueAvailable()) {
                if (!isset($setParameters[$i])) {
                    throw new \Exception('The class ' . $class . ' requires the parameter ' . $parameter->name . ' to be set as the ' . ($i + 1) . ' parameter. Define the method classParameters() in your class to set the parameters. It should be the same order as the constructor parameters.');
                }
            }
            $i++;
        }
    }

    /**
     * Convert a native PHP type to a Laravel validation rule
     *
     * @param string $type
     * @return string
     */
    private function convertPHPTypeToLaravelValidationRule(string $type): string
    {
        switch ($type) {
            case 'int':
                return 'integer';
            case 'string':
                return 'string';
            case 'bool':
                return 'boolean';
            case 'float':
                return 'float';
            case 'array':
                return 'array';
            case 'object':
                return 'object';
            case 'null':
                return 'nullable';
            default:
                return 'string';
        }
    }

    /**
     * Generate Laravel validation rules from a methods parameters
     *
     * @param \ReflectionMethod|string $method the name of the method to generate the rules for
     * @return array
     */
    private function generateRulesFromMethodParameters($method): array
    {
        // Get the parameters that the method takes
        $reflection = $this->getReflectionMethod($method);
        $params = $reflection->getParameters();

        $rules = [];

        foreach ($params as $param) {
            $rulesForParam = [];

            // If the rule has a default value, then make the rule nullable
            $rulesForParam[] = $param->isDefaultValueAvailable() ? 'nullable' : 'required';

            // If the parameter is variadic, add the array rule. Otherwise if a type exists, just add the type rule
            if ($param->isVariadic()) {
                $rulesForParam[] = 'array';
                // If the parameter is typed, add the type rule to each element
                if ($param->getType() !== null) {
                    $rules[$param->getName() . '.*'] = $this->convertPHPTypeToLaravelValidationRule($param->getType()->getName());
                }
            } elseif ($param->getType() !== null) {
                $rulesForParam[] = $this->convertPHPTypeToLaravelValidationRule($param->getType()->getName());
            }

            // If the parameter is variadic, add the array rule
            if ($param->isVariadic()) {
                $rulesForParam[] = 'array';
            }

            $rules[$param->getName()] = $rulesForParam;
        }

        return $rules;
    }

    /**
     * Get the validated data for a given method or return a validation error response
     *
     * @param \ReflectionMethod|string $method
     * @throws ValidationException
     * @return array
     */
    protected function getValidatedData($method): array
    {
        $rules = $this->generateRulesFromMethodParameters($method);

        // Merge the route params with the request
        request()->merge(request()->route()->parameters());

        // Validate the request
        $validator = validator(request()->all(), $rules);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Get the validated data
        return $this->destructureVariadicParameters($method, $validator->validated());
    }

    /**
     * Get an instance of the class that is being inherited from
     *
     * @return object
     */
    protected function class(): object
    {
        return $this->classInstance;
    }

    /**
     * Get the name of the class that is being inherited from, using the name of the controller
     *
     * @return string|null
     */
    private function getNameFromParentClassName(): ?string
    {
        // get the name of the class that is extending this one
        $parentClass = get_class($this);
        // If the parent class name ends in ClassController
        if (substr($parentClass, -strlen($this->classControllerName)) === $this->classControllerName) {
            // get the name of the class that is extending this one
            $parentClassName = explode('\\', $parentClass);
            $parentClassName = end($parentClassName);
            // remove the ClassController from the end of the class name
            $parentClassName = substr($parentClassName, 0, -strlen($this->classControllerName));
            // return the name of the class that is extending this one
            return $parentClassName;
        }
        return null;
    }

    /**
     * Destrcture the variadic parameters from the validated data
     *
     * @param \ReflectionMethod|string $method
     * @param array $data
     * @return array of data with the variadic parameters destructured
     */
    private function destructureVariadicParameters(string $method, array $data): array
    {
        // If we found a variadic parameter, we need to unpack the array that is in $data[$variadicParameter]
        if ($variadicParameter = $this->getVariadicParameter($method)) {
            $variadicData = $data[$variadicParameter];
            unset($data[$variadicParameter]);
            array_push($data, ...$variadicData);
        }

        return $data;
    }

    /**
     * Get the variadic parameter for a given method
     *
     * @param \ReflectionMethod|string $method
     * @return string|null the name of the variadic parameter
     */
    private function getVariadicParameter($method): ?string
    {
        // Check if one of the parameters is a variadic parameter, if so, we need to unpack the array
        $reflectionMethod = $this->getReflectionMethod($method);
        $parameters = $reflectionMethod->getParameters();

        // Find the variadic parameter
        $variadicParameter = null;
        foreach ($parameters as $parameter) {
            if ($parameter->isVariadic()) {
                $variadicParameter = $parameter->getName();
                break;
            }
        }
        return $variadicParameter;
    }

    /**
     * Get an instance of a reflection method if the passed $method is not already a reflection method
     *
     * @param \ReflectionMethod|string $method
     * @return \ReflectionMethod
     */
    private function getReflectionMethod($method): \ReflectionMethod
    {
        if ($method instanceof \ReflectionMethod) {
            return $method;
        }
        return new \ReflectionMethod($this->classInstance, $method);
    }
}
