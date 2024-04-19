<?php

namespace HiFolks\Fusion\Tests;

use HiFolks\Fusion\FusionServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            FusionServiceProvider::class,
        ];
    }
}
