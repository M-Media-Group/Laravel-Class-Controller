<?php

namespace MMedia\ClassController\Examples;

/**
 * This is an example class that will later be used in a Controller that extends ClassController. Note that this file and its respective Controller are also used in the tests.
 */
class Test
{
    public function __construct()
    {
    }

    public function noParams()
    {
        return true;
    }

    public function stringParam(string $param)
    {
        return true;
    }

    public function intParam(int $param)
    {
        return true;
    }

    public function floatParam(float $param)
    {
        return true;
    }

    public function boolParam(bool $param)
    {
        return true;
    }

    public function arrayParam(array $param)
    {
        return true;
    }

    public function objectParam(\stdClass $param)
    {
        return true;
    }

    public function untypedParam($param)
    {
        return true;
    }

    public function mixedParam(mixed $param)
    {
        return true;
    }

    public function intOrFloatParam(int|float $param)
    {
        return true;
    }

    public function mixedParamWithDefault($param = null)
    {
        return true;
    }

    public function mixedParamWithDefaultAndVariadic($param = null, ...$other)
    {
        return true;
    }

    public function stringVariadicParam(string ...$param)
    {
        return true;
    }

    public function intVariadicParam(int ...$param)
    {
        return true;
    }

    public function floatVariadicParam(float ...$param)
    {
        return true;
    }

    public function boolVariadicParam(bool ...$param)
    {
        return true;
    }

    public function arrayVariadicParam(array ...$param)
    {
        return true;
    }

    public function objectVariadicParam(\stdClass ...$param)
    {
        return true;
    }

    public function mixedVariadicParam(mixed ...$param)
    {
        return true;
    }

    public function throwsException()
    {
        throw new \Exception('Exception thrown');
    }
}
