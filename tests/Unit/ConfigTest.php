<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

test('Config Environment', function () {
    expect(Config::get('app.env'))->toBe('testing');
    expect(App::environment())->toBe('testing');
});
