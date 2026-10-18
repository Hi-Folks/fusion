<?php

use HiFolks\Fusion\ContentCache;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

function contentCacheFixture(): string
{
    $directory = sys_get_temp_dir() . '/fusion-content-cache-' . bin2hex(random_bytes(8));
    mkdir($directory);

    return $directory;
}

function removeContentCacheFixture(string $directory): void
{
    foreach (new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    ) as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir($directory);
}

test('it reuses resolved rows while the content is unchanged', function () {
    $directory = contentCacheFixture();
    file_put_contents($directory . '/page.md', "---\ntitle: First\n---\nBody");
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
        removeContentCacheFixture($directory);
    }
});

test('it resolves rows again when content changes', function () {
    $directory = contentCacheFixture();
    $file = $directory . '/page.md';
    file_put_contents($file, "---\ntitle: First\n---\nBody");
    $cache = new ContentCache(Cache::store('array'));
    $resolutions = 0;

    $resolveRows = function () use (&$resolutions): array {
        $resolutions++;

        return [['resolution' => $resolutions]];
    };

    try {
        $first = $cache->rememberRows('Page', $directory, ['title'], $resolveRows);
        file_put_contents($file, "---\ntitle: Updated\n---\nBody");
        $second = $cache->rememberRows('Page', $directory, ['title'], $resolveRows);

        expect($first)->not->toBe($second)
            ->and($resolutions)->toBe(2);
    } finally {
        removeContentCacheFixture($directory);
    }
});

test('it uses the configured ttl for cached rows', function () {
    $directory = contentCacheFixture();
    file_put_contents($directory . '/page.md', "---\ntitle: First\n---\nBody");
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
        removeContentCacheFixture($directory);
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
