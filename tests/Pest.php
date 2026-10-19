<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(HiFolks\Fusion\Tests\TestCase::class)->in('Feature');
uses(HiFolks\Fusion\Tests\TestCase::class)->in('Unit');
uses(HiFolks\Fusion\Tests\TestCase::class)->in('Commands');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn() => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function copyTestDataFile(string $fixture, string $destination): void
{
    $source = __DIR__ . '/Data/' . $fixture;
    $directory = dirname($destination);

    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    if (! copy($source, $destination)) {
        throw new RuntimeException("Unable to copy test data fixture [{$fixture}].");
    }
}

function createTemporaryTestDirectory(string $prefix): string
{
    $directory = sys_get_temp_dir() . '/' . $prefix . '-' . bin2hex(random_bytes(8));

    if (! mkdir($directory, 0777, true) && ! is_dir($directory)) {
        throw new RuntimeException("Unable to create temporary test directory [{$directory}].");
    }

    return $directory;
}

function removeTemporaryTestDirectory(string $directory): void
{
    if (is_dir($directory)) {
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($directory);
    }
}
