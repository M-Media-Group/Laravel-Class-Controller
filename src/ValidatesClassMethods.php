<?php

namespace MMedia\ClassController;

use Illuminate\Validation\ValidationException;

trait ValidatesClassMethods
{
    /**
     * Convert a native PHP type to a Laravel validation rule
     *
     * @param string $type
     * @return string
     */
    protected function convertPHPTypeToLaravelValidationRule(string $type): string
    {
        switch ($type) {
            case 'mixed':
                return '';
            case 'integer':
            case 'int':
                return 'integer';
            case 'string':
                return 'string';
            case 'boolean':
            case 'bool':
                return 'boolean';
            case 'double':
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
     * Validate the parameters that the class takes
     *
     * @param object|string $class
     * @param iterable $setParameters parameters that are already set / to be validated against
     * @return void
     */
    protected function validateConstructorParameters($class, iterable $setParameters): void
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
     * Get a set of rules for a given parameter
     *
     * @param \ReflectionParameter $param
     * @return array of Laravel validation rules
     */
    protected function getLaravelRulesForParam(\ReflectionParameter $param): array
    {
        // If is typeof union ReflectionType, convert it to a string
        if ($param->getType() instanceof \ReflectionUnionType) {
            $types = $param->getType()->getTypes();
            // For each type, convert it to a string and add it to the array
            $types = array_map(function ($type) {
                return $this->convertPHPTypeToLaravelValidationRule($type->getName());
            }, $types);

            $callable = function ($attribute, $value, $fail) use ($types) {
                // Check if value is instanceof of any type
                $valueType = $this->convertPHPTypeToLaravelValidationRule(gettype($value));

                // If the value is not an instanceof any type, fail
                if (!in_array($valueType, $types)) {
                    $fail('The ' . $attribute . ' must be a type of ' . implode(' or ', $types) . '.');
                }
            };
            return [$callable];
        }

        // Else just get the name and convert it
        $type = $param->getType()->getName();
        return [$this->convertPHPTypeToLaravelValidationRule($type)];
    }

    /**
     * Generate Laravel validation rules from a methods parameters
     *
     * @param string|object $class
     * @param \Reflection $method
     * @param \ReflectionMethod|string $method the name of the method to generate the rules for
     * @return array
     */
    private function generateRulesFromMethodParameters($class, $method): array
    {
        // Get the parameters that the method takes
        $reflection = $this->getReflectionMethod($class, $method);

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
                    $rules[$param->getName() . '.*'] = $this->getLaravelRulesForParam($param);
                }
            } elseif ($param->getType() !== null) {
                // Merge the rules for the parameter with the rules for the parameter
                $rulesForParam = array_merge($rulesForParam, $this->getLaravelRulesForParam($param));
            }

            $rules[$param->getName()] = $rulesForParam;
        }
        return $rules;
    }

    /**
     * Get the validated data for a given method or return a validation error response
     *
     * @param string|object $class
     * @param \ReflectionMethod|string $method
     * @throws ValidationException
     * @return array
     */
    protected function getValidatedData($class, $method): array
    {
        $rules = $this->generateRulesFromMethodParameters($class, $method);

        // Merge the route params with the request
        request()->merge(request()->route()->parameters());

        // Validate the request
        $validator = validator(request()->all(), $rules);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Get the validated data
        return $this->destructureVariadicParameters($class, $method, $validator->validated());
    }

    /**
     * Destrcture the variadic parameters from the validated data
     *
     * @param string|object $class
     * @param \ReflectionMethod|string $method
     * @param array $data
     * @return array of data with the variadic parameters destructured
     */
    private function destructureVariadicParameters($class, string $method, array $data): array
    {
        // If we found a variadic parameter, we need to unpack the array that is in $data[$variadicParameter]
        if ($variadicParameter = $this->getVariadicParameter($class, $method)) {
            $variadicData = $data[$variadicParameter];
            unset($data[$variadicParameter]);
            array_push($data, ...$variadicData);
        }

        return $data;
    }

    /**
     * Get the variadic parameter for a given method
     *
     * @param string|object $class
     * @param \ReflectionMethod|string $method
     * @return string|null the name of the variadic parameter
     */
    private function getVariadicParameter($class, $method): ?string
    {
        // Check if one of the parameters is a variadic parameter, if so, we need to unpack the array
        $reflectionMethod = $this->getReflectionMethod($class, $method);
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
     * @param string|object $class
     * @param \ReflectionMethod|string $method
     * @return \ReflectionMethod
     */
    private function getReflectionMethod($class, $method): \ReflectionMethod
    {
        if ($method instanceof \ReflectionMethod) {
            return $method;
        }
        return new \ReflectionMethod($class, $method);
    }
}
