<?php

use HiFolks\Fusion\ContentCache;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

function contentCacheFixture(): string
{
    $directory = createTemporaryTestDirectory('fusion-content-cache');
    copyTestDataFile('markdown/cache-page.md', $directory . '/page.md');

    return $directory;
}

test('it reuses resolved rows while the content is unchanged', function () {
    $directory = contentCacheFixture();
    $cache = new ContentCache(Cache::store('array'));
    $resolutions = 0;

    $resolveRows = function () use (&$resolutions): array {
        $resolutions++;

        return [['title' => 'First']];
    };

    try {
        $first = $cache->rememberRows('Page', $directory, ['title'], $resolveRows);
        $second = $cache->rememberRows('Page', $directory, ['title'], $resolveRows);

        expect($first)->toBe($second)
            ->and($resolutions)->toBe(1);
    } finally {
        removeTemporaryTestDirectory($directory);
    }
});

test('it resolves rows again when content changes', function () {
    $directory = contentCacheFixture();
    $file = $directory . '/page.md';
    $cache = new ContentCache(Cache::store('array'));
    $resolutions = 0;

    $resolveRows = function () use (&$resolutions): array {
        $resolutions++;

        return [['resolution' => $resolutions]];
    };

    try {
        $first = $cache->rememberRows('Page', $directory, ['title'], $resolveRows);
        copyTestDataFile('markdown/cache-page-updated.md', $file);
        $second = $cache->rememberRows('Page', $directory, ['title'], $resolveRows);

        expect($first)->not->toBe($second)
            ->and($resolutions)->toBe(2);
    } finally {
        removeTemporaryTestDirectory($directory);
    }
});

test('it uses the configured ttl for cached rows', function () {
    $directory = contentCacheFixture();
    $repository = Mockery::mock(Repository::class);
    $repository->shouldReceive('remember')
        ->once()
        ->withArgs(fn(string $key, int $ttl): bool => str_starts_with($key, 'fusion:rows:') && $ttl === 120)
        ->andReturn([['title' => 'First']]);
    $cache = new ContentCache($repository, 120);

    try {
        expect($cache->rememberRows('Page', $directory, ['title'], fn(): array => []))
            ->toBe([['title' => 'First']]);
    } finally {
        removeTemporaryTestDirectory($directory);
    }
});

test('the service provider resolves content cache from Laravel cache configuration', function () {
    expect(app(ContentCache::class))->toBeInstanceOf(ContentCache::class);
});

test('the service provider reads the ttl from Fusion configuration', function () {
    config(['fusion.cache_ttl' => 120]);
    app()->forgetInstance(ContentCache::class);

    $ttl = (new ReflectionProperty(ContentCache::class, 'ttl'))->getValue(app(ContentCache::class));

    expect($ttl)->toBe(120);
});
