<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

test('Config Environment', function () {
    expect(Config::get('app.env'))->toBe('workbench');
    expect(App::environment())->toBe('testing');
});
