<?php

use HiFolks\Fusion\Models\Page;

function pageModelWithoutBootingSushi(): Page
{
    return (new \ReflectionClass(Page::class))->newInstanceWithoutConstructor();
}

test('content directory configuration resolves relative paths from the application root', function () {
    $previousDirectory = config('fusion.content_directory');
    config(['fusion.content_directory' => 'content/markdown']);

    try {
        expect(pageModelWithoutBootingSushi()->getResourceFolder())
            ->toBe(base_path('content/markdown/page'));
    } finally {
        config(['fusion.content_directory' => $previousDirectory]);
    }
});

test('content directory configuration supports absolute paths', function () {
    $contentDirectory = createTemporaryTestDirectory('fusion-absolute-content');
    $previousDirectory = config('fusion.content_directory');
    config(['fusion.content_directory' => $contentDirectory]);

    try {
        expect(pageModelWithoutBootingSushi()->getResourceFolder())
            ->toBe($contentDirectory . DIRECTORY_SEPARATOR . 'page');
    } finally {
        config(['fusion.content_directory' => $previousDirectory]);
        removeTemporaryTestDirectory($contentDirectory);
    }
});

test('content directory defaults to the Laravel resources content directory', function () {
    $previousDirectory = config('fusion.content_directory');
    config(['fusion.content_directory' => null]);

    try {
        expect(pageModelWithoutBootingSushi()->getResourceFolder())
            ->toBe(resource_path('content/page'));
    } finally {
        config(['fusion.content_directory' => $previousDirectory]);
    }
});

test('Model all() method', function () {
    $pages = Page::all();
    expect($pages->count())->toBe(3);
    expect($pages->get(0)->title)->toBe('Page Title 1');
    expect($pages->get(1)->title)->toBe('Page Title 2');
    expect($pages->get(2)->title)->toBe('Page Title 3');

    expect($pages->get(1)->body)->toContain('Page 2');
});

test('Model highlights fenced code blocks', function () {
    $page = Page::where('title', 'Page Title 1')->firstOrFail();

    expect($page->body)
        ->toContain('<pre data-lang="php" class="notranslate">')
        ->toContain('<span class="hl-keyword">&lt;?php</span>')
        ->toContain('<span class="hl-keyword">echo</span>');
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
