<?php

namespace HiFolks\Fusion\Tests;

use HiFolks\Fusion\FusionServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
    }

    protected function getPackageProviders($app)
    {
        return [
            FusionServiceProvider::class,
        ];
    }
}
