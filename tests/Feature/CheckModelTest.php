<?php

test('console command', function () {
    $this->artisan('fusion:check', ['--dir' => __DIR__.'/../Data/content'])->assertExitCode(0);
});
