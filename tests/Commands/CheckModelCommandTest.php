<?php

use HiFolks\Fusion\Traits\FusionBaseModelTrait;
use Illuminate\Database\Eloquent\Model;

class CheckModelValidFixture extends Model
{
    use FusionBaseModelTrait;
}

class CheckModelMissingTraitFixture extends Model {}

class CheckModelNotEloquentFixture
{
    use FusionBaseModelTrait;
}

test('check-model succeeds when the class extends Eloquent and uses the Fusion trait', function () {
    $this->artisan('fusion:check-model', ['--model' => CheckModelValidFixture::class])
        ->assertExitCode(0);
});

test('check-model fails when the class does not extend Eloquent', function () {
    $this->artisan('fusion:check-model', ['--model' => CheckModelNotEloquentFixture::class])
        ->assertExitCode(1);
});

test('check-model fails when the class does not use the Fusion trait', function () {
    $this->artisan('fusion:check-model', ['--model' => CheckModelMissingTraitFixture::class])
        ->assertExitCode(1);
});

test('check-model returns invalid when the class does not exist', function () {
    $this->artisan('fusion:check-model', ['--model' => 'MissingCheckModelFixture'])
        ->assertExitCode(2);
});

test('interactive check-model searches discovered fully qualified class names', function () {
    $appPath = sys_get_temp_dir() . '/fusion-interactive-check-model-' . bin2hex(random_bytes(8));
    $modelsPath = $appPath . '/Models';
    mkdir($modelsPath, 0777, true);
    file_put_contents($modelsPath . '/InteractiveCheckModel.php', "<?php\nnamespace App\\Models;\nclass InteractiveCheckModel {}\n");
    $originalAppPath = app()->path();
    app()->useAppPath($appPath);
    class_alias(CheckModelValidFixture::class, 'App\\Models\\InteractiveCheckModel');

    try {
        $this->artisan('fusion:check-model', ['--interactive' => true])
            ->expectsQuestion('Search for a model to check', 'InteractiveCheckModel')
            ->expectsChoice(
                'Search for a model to check',
                'App\\Models\\InteractiveCheckModel',
                ['App\\Models\\InteractiveCheckModel' => 'App\\Models\\InteractiveCheckModel'],
            )
            ->expectsOutputToContain('App\\Models\\InteractiveCheckModel')
            ->assertExitCode(0);
    } finally {
        unlink($modelsPath . '/InteractiveCheckModel.php');
        rmdir($modelsPath);
        rmdir($appPath);
        app()->useAppPath($originalAppPath);
    }
});

test('interactive check-model requires a terminal', function () {
    $this->artisan('fusion:check-model', ['--interactive' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Interactive model checking requires an interactive terminal.')
        ->assertExitCode(2);
});

test('check-model lists model classes discovered in app Models', function () {
    $appPath = sys_get_temp_dir() . '/fusion-model-list-' . bin2hex(random_bytes(8));
    $modelsPath = $appPath . '/Models';
    mkdir($modelsPath, 0777, true);
    file_put_contents($modelsPath . '/DiscoveredArticle.php', "<?php\nnamespace App\\Models;\nclass DiscoveredArticle {}\n");
    file_put_contents($modelsPath . '/PlainArticle.php', "<?php\nnamespace App\\Models;\nclass PlainArticle {}\n");
    app()->useAppPath($appPath);
    class_alias(CheckModelValidFixture::class, 'App\\Models\\DiscoveredArticle');
    class_alias(CheckModelMissingTraitFixture::class, 'App\\Models\\PlainArticle');

    try {
        $exitCode = \Illuminate\Support\Facades\Artisan::call('fusion:check-model');
        $output = \Illuminate\Support\Facades\Artisan::output();

        expect($exitCode)->toBe(0)
            ->and($output)->toContain('App\\Models\\DiscoveredArticle')
            ->and($output)->toContain('Fusion compatible')
            ->and($output)->toContain('App\\Models\\PlainArticle')
            ->and($output)->toContain('Missing FusionBaseModelTrait')
            ->and($output)->toContain('backslashes can be interpreted by the shell')
            ->and($output)->toContain('--model="App\\Models\\YourModel"');
    } finally {
        foreach (glob($modelsPath . '/*') as $file) {
            unlink($file);
        }
        rmdir($modelsPath);
        rmdir($appPath);
    }
});

test('check-model accepts a custom directory and discovers declared namespaces', function () {
    $modelsPath = sys_get_temp_dir() . '/fusion-custom-models-' . bin2hex(random_bytes(8));
    mkdir($modelsPath);
    file_put_contents($modelsPath . '/DiscoveredPage.php', "<?php\nnamespace App\\Domain\\Models;\nclass DiscoveredPage {}\n");
    class_alias(CheckModelValidFixture::class, 'App\\Domain\\Models\\DiscoveredPage');

    try {
        $exitCode = \Illuminate\Support\Facades\Artisan::call('fusion:check-model', ['--dir' => $modelsPath]);
        $output = \Illuminate\Support\Facades\Artisan::output();

        expect($exitCode)->toBe(0)
            ->and($output)->toContain('App\\Domain\\Models\\DiscoveredPage')
            ->and($output)->toContain('Fusion compatible');
    } finally {
        unlink($modelsPath . '/DiscoveredPage.php');
        rmdir($modelsPath);
    }
});
