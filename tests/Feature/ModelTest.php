<?php

use HiFolks\Fusion\Models\Page;

test('Model all() method', function () {
    $pages = Page::all();
    expect($pages->count())->toBe(3);
    expect($pages->get(0)->title)->toBe('Page Title 1');
    expect($pages->get(1)->title)->toBe('Page Title 2');
    expect($pages->get(2)->title)->toBe('Page Title 3');

    expect($pages->get(1)->body)->toContain('Page 2');
});

test('Model where() method', function () {
    $pages = Page::where('title', 'Page Title 2')->get();
    expect($pages->count())->toBe(1);
    expect($pages->get(0)->title)->toBe('Page Title 2');
    expect($pages->get(0)->body)->toContain('Page 2');
});

test('Model orderBy() method', function () {
    $pages = Page::whereNot('title', 'Page Title 2')
        ->orderByDesc('title')->get();
    expect($pages->count())->toBe(2);
    expect($pages->get(0)->title)->toBe('Page Title 3');
    expect($pages->get(0)->body)->toContain('Page 3');
    expect($pages->get(1)->title)->toBe('Page Title 1');
    expect($pages->get(1)->body)->toContain('Page 1');
});
