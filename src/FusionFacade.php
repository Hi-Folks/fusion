<?php

declare(strict_types=1);

namespace HiFolks\Fusion;

use Illuminate\Support\Facades\Facade;

/**
 * @see \HiFolks\Fusion\Skeleton\SkeletonClass
 */
class FusionFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'fusion';
    }
}
