<?php

namespace MMedia\ClassController;

use Illuminate\Support\Facades\Facade;

/**
 * @see \MMedia\ClassController\Skeleton\SkeletonClass
 */
class ClassControllerFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'classcontroller';
    }
}
