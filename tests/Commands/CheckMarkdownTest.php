<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Process\FakeProcessResult;
use Illuminate\Process\PendingProcess;
use HiFolks\Fusion\Tests\Fixtures\Models\ArticleWithFrontmatterRules;

function createCheckMarkdownFixture(string $fixture): string
{
    $root = createTemporaryTestDirectory('fusion-check');
    copyTestDataFile('markdown/' . $fixture, $root . '/article/page.md');

    return $root;
}

function fakeGitChangedFiles(string $root, string $statusOutput): void
{
    Process::fake(function (PendingProcess $process) use ($root, $statusOutput): FakeProcessResult {
        return match ($process->command) {
            ['git', 'rev-parse', '--show-toplevel'] => new FakeProcessResult(output: $root),
            ['git', 'status', '--porcelain=v1', '-z', '--untracked-files=all', '--', '.'] => new FakeProcessResult(output: $statusOutput),
            default => new FakeProcessResult(exitCode: 1, errorOutput: 'Unexpected Git command.'),
        };
    });
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
    $root = createCheckMarkdownFixture('malformed-frontmatter.md');

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
        removeTemporaryTestDirectory($root);
    }
});

test('deep validation renders markdown and returns success for valid content', function () {
    $root = createCheckMarkdownFixture('valid-page.md');

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
        removeTemporaryTestDirectory($root);
    }
});

test('changed mode validates modified and untracked markdown files only', function () {
    $root = createCheckMarkdownFixture('original-page.md');
    copyTestDataFile('markdown/unchanged-page.md', $root . '/article/unchanged.md');
    copyTestDataFile('markdown/malformed-frontmatter.md', $root . '/article/page.md');
    copyTestDataFile('markdown/added-page.md', $root . '/article/added.md');
    fakeGitChangedFiles($root, " M article/page.md\0?? article/added.md\0");

    try {
        $exitCode = Artisan::call('fusion:check', [
            '--dir' => $root,
            '--validate' => true,
            '--changed' => true,
            '--include-valid' => true,
            '--format' => 'json',
        ]);
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $reportedFiles = array_column($report['files'], 'file');

        expect($exitCode)->toBe(1)
            ->and($report['summary']['files'])->toBe(2)
            ->and($report['summary']['invalid'])->toBe(1)
            ->and($reportedFiles)->toBe(['article/added.md', 'article/page.md'])
            ->and($report['findings'][0]['file'])->toBe('article/page.md')
            ->and($report['findings'][0]['rule'])->toBe('frontmatter.parse');

        Process::assertRanTimes(['git', 'rev-parse', '--show-toplevel'], 1);
        Process::assertRanTimes(['git', 'status', '--porcelain=v1', '-z', '--untracked-files=all', '--', '.'], 1);
    } finally {
        removeTemporaryTestDirectory($root);
    }
});

test('changed mode reports an error when the content directory is not in a git repository', function () {
    $root = createCheckMarkdownFixture('page.md');
    Process::fake(fn(PendingProcess $process): FakeProcessResult => new FakeProcessResult(
        exitCode: $process->command === ['git', 'rev-parse', '--show-toplevel'] ? 128 : 1,
        errorOutput: 'fatal: not a git repository',
    ));

    try {
        $exitCode = Artisan::call('fusion:check', [
            '--dir' => $root,
            '--changed' => true,
            '--format' => 'json',
        ]);
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        expect($exitCode)->toBe(2)
            ->and($report['status'])->toBe('failed')
            ->and($report['findings'][0]['rule'])->toBe('git.changed_files_unavailable');
    } finally {
        removeTemporaryTestDirectory($root);
    }
});

test('model validation applies Laravel frontmatter rules and reports errors per file', function () {
    $root = createCheckMarkdownFixture('valid-article.md');
    copyTestDataFile('markdown/invalid-article.md', $root . '/article/invalid.md');

    try {
        $exitCode = Artisan::call('fusion:check', [
            '--dir' => $root,
            '--validate' => true,
            '--model' => ArticleWithFrontmatterRules::class,
            '--include-valid' => true,
            '--format' => 'json',
        ]);
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $validationFindings = array_values(array_filter(
            $report['findings'],
            static fn(array $finding): bool => $finding['rule'] === 'frontmatter.validation',
        ));
        expect($exitCode)->toBe(1)
            ->and($report['summary']['files'])->toBe(2)
            ->and($report['summary']['valid'])->toBe(1)
            ->and($report['summary']['invalid'])->toBe(1)
            ->and($report['files'][0]['valid'])->toBeFalse()
            ->and($validationFindings)->toHaveCount(2)
            ->and($validationFindings[0]['file'])->toBe('article/invalid.md')
            ->and($validationFindings[0]['message'])->toContain('title')
            ->and($validationFindings[1]['message'])->toContain('date');
    } finally {
        removeTemporaryTestDirectory($root);
    }
});

test('model frontmatter rules are opt-in and require deep validation', function () {
    $root = createCheckMarkdownFixture('no-schema-article.md');

    try {
        $exitCode = Artisan::call('fusion:check', [
            '--dir' => $root,
            '--validate' => true,
            '--format' => 'json',
        ]);
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        expect($exitCode)->toBe(0)
            ->and($report['summary']['errors'])->toBe(0);

        $exitCode = Artisan::call('fusion:check', [
            '--dir' => $root,
            '--model' => ArticleWithFrontmatterRules::class,
            '--format' => 'json',
        ]);
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        expect($exitCode)->toBe(2)
            ->and($report['findings'][0]['rule'])->toBe('frontmatter.model_invalid')
            ->and($report['findings'][0]['message'])->toContain('--validate');
    } finally {
        removeTemporaryTestDirectory($root);
    }
});
