<?php

use Illuminate\Support\Facades\Artisan;

function createCheckMarkdownFixture(string $contents): string
{
    $root = sys_get_temp_dir() . '/fusion-check-' . bin2hex(random_bytes(8));
    mkdir($root . '/article', 0777, true);
    file_put_contents($root . '/article/page.md', $contents);

    return $root;
}

function removeCheckMarkdownFixture(string $root): void
{
    foreach (new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    ) as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir($root);
}

test('check command emits machine-readable json without table output', function () {
    $exitCode = Artisan::call('fusion:check', [
        '--dir' => __DIR__ . '/../Data/content',
        '--format' => 'json',
    ]);
    $output = Artisan::output();
    $report = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

    expect($exitCode)->toBe(0)
        ->and($report['status'])->toBe('ok')
        ->and($report['summary']['files'])->toBe(4)
        ->and($report['findings'])->toBe([]);
});

test('deep validation reports malformed frontmatter and exits with an error', function () {
    $root = createCheckMarkdownFixture("---\ntitle: [broken\n---\n# Body\n");

    try {
        $exitCode = Artisan::call('fusion:check', [
            '--dir' => $root,
            '--validate' => true,
            '--format' => 'json',
        ]);
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        expect($exitCode)->toBe(1)
            ->and($report['status'])->toBe('failed')
            ->and($report['summary']['errors'])->toBe(1)
            ->and($report['findings'][0]['rule'])->toBe('frontmatter.parse');
    } finally {
        removeCheckMarkdownFixture($root);
    }
});

test('deep validation renders markdown and returns success for valid content', function () {
    $root = createCheckMarkdownFixture("---\ntitle: Valid page\n---\n# Body\n\n```php\necho 'valid';\n```\n");

    try {
        $exitCode = Artisan::call('fusion:check', [
            '--dir' => $root,
            '--validate' => true,
            '--format' => 'json',
        ]);
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        expect($exitCode)->toBe(0)
            ->and($report['status'])->toBe('ok')
            ->and($report['summary']['files'])->toBe(1)
            ->and($report['summary']['valid'])->toBe(1)
            ->and($report['files'])->toBe([]);

        $exitCode = Artisan::call('fusion:check', [
            '--dir' => $root,
            '--validate' => true,
            '--include-valid' => true,
            '--format' => 'json',
        ]);
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        expect($exitCode)->toBe(0)
            ->and($report['files'][0]['fields'])->toBe(['title']);
    } finally {
        removeCheckMarkdownFixture($root);
    }
});
