<?php

test('Sync Model command', function () {
    $this->artisan('fusion:sync-model', ['--path' => __DIR__.'/../Data/content/article'])
        ->expectsOutputToContain('app/Models/Article.php')
        ->assertExitCode(true);
});
