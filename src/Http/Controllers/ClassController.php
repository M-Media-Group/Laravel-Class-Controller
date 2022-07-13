<?php

namespace MMedia\ClassController\Http\Controllers;

// use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MMedia\ClassController\ValidatesClassMethods;

class ClassController extends Controller
{

    use ValidatesClassMethods;

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
     * Get an instance of the class that is being inherited from
     *
     * @return object
     */
    public function class(): object
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
            return parent::__call($method, $parameters);
        }

        $data = $this->getValidatedData($this->inheritedClass, $method);

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
}
