<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

test('example', function (): void {
    expect(true)->toBeTrue();
});

test('confirm environment is set to testing', function () {
    expect(Config::get('app.env'))->toBe('testing');

    expect(App::environment())->toBe('testing');
});
