<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Process\FakeProcessResult;
use Illuminate\Process\PendingProcess;
use HiFolks\Fusion\Tests\Fixtures\Models\ArticleWithFrontmatterRules;
use HiFolks\Fusion\Tests\Fixtures\Models\ArticleWithoutFrontmatterRules;

function createCheckMarkdownFixture(string $fixture): string
{
    $root = createTemporaryTestDirectory('fusion-check');
    copyTestDataFile('markdown/' . $fixture, $root . '/article/page.md');

    return $root;
}

function fakeGitChangedFiles(string $root, string $statusOutput, ?string $baseRef = null, string $diffOutput = ''): void
{
    Process::fake(function (PendingProcess $process) use ($root, $statusOutput, $baseRef, $diffOutput): FakeProcessResult {
        if ($process->command === ['git', 'rev-parse', '--show-toplevel']) {
            return new FakeProcessResult(output: $root);
        }

        if ($baseRef !== null && $process->command === [
            'git', 'diff', '--name-only', '-z', '--diff-filter=ACMR', $baseRef . '...HEAD', '--', '.',
        ]) {
            return new FakeProcessResult(output: $diffOutput);
        }

        if ($process->command === ['git', 'status', '--porcelain=v1', '-z', '--untracked-files=all', '--', '.']) {
            return new FakeProcessResult(output: $statusOutput);
        }

        return new FakeProcessResult(exitCode: 1, errorOutput: 'Unexpected Git command.');
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
    copyTestDataFile('markdown/added-page.md', $root . '/article/staged.md');
    copyTestDataFile('markdown/malformed-frontmatter.md', $root . '/article/page.md');
    copyTestDataFile('markdown/added-page.md', $root . '/article/added.md');
    fakeGitChangedFiles($root, "M  article/staged.md\0 M article/page.md\0?? article/added.md\0");

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
            ->and($report['summary']['files'])->toBe(3)
            ->and($report['summary']['invalid'])->toBe(1)
            ->and($reportedFiles)->toBe(['article/added.md', 'article/page.md', 'article/staged.md'])
            ->and($report['findings'][0]['file'])->toBe('article/page.md')
            ->and($report['findings'][0]['rule'])->toBe('frontmatter.parse');

        Process::assertRanTimes(['git', 'rev-parse', '--show-toplevel'], 1);
        Process::assertRanTimes(['git', 'status', '--porcelain=v1', '-z', '--untracked-files=all', '--', '.'], 1);
    } finally {
        removeTemporaryTestDirectory($root);
    }
});

test('changed mode selects committed Markdown files relative to a base ref', function () {
    $root = createCheckMarkdownFixture('original-page.md');
    copyTestDataFile('markdown/unchanged-page.md', $root . '/article/unchanged.md');
    copyTestDataFile('markdown/malformed-frontmatter.md', $root . '/article/page.md');
    copyTestDataFile('markdown/added-page.md', $root . '/article/added.md');
    fakeGitChangedFiles($root, '', 'origin/main', "article/page.md\0article/added.md\0");

    try {
        $exitCode = Artisan::call('fusion:check', [
            '--dir' => $root,
            '--validate' => true,
            '--changed' => true,
            '--base' => 'origin/main',
            '--include-valid' => true,
            '--format' => 'json',
        ]);
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        expect($exitCode)->toBe(1)
            ->and($report['summary']['files'])->toBe(2)
            ->and(array_column($report['files'], 'file'))->toBe(['article/added.md', 'article/page.md'])
            ->and($report['findings'][0]['rule'])->toBe('frontmatter.parse');

        Process::assertRan(['git', 'diff', '--name-only', '-z', '--diff-filter=ACMR', 'origin/main...HEAD', '--', '.']);
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

test('model validation works without frontmatterRules and does not invoke an Eloquent query', function () {
    $root = createCheckMarkdownFixture('page.md');

    try {
        $exitCode = Artisan::call('fusion:check', [
            '--dir' => $root,
            '--validate' => true,
            '--model' => ArticleWithoutFrontmatterRules::class,
            '--format' => 'json',
        ]);
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        expect($exitCode)->toBe(0)
            ->and($report['status'])->toBe('ok')
            ->and($report['summary']['files'])->toBe(1)
            ->and($report['summary']['valid'])->toBe(1)
            ->and($report['summary']['errors'])->toBe(0)
            ->and($report['summary']['warnings'])->toBe(1)
            ->and($report['findings'][0]['file'])->toBeNull()
            ->and($report['findings'][0]['rule'])->toBe('frontmatter.rules_not_defined')
            ->and($report['findings'][0]['message'])->toContain('frontmatterRules()');
    } finally {
        removeTemporaryTestDirectory($root);
    }
});

test('unknown field warnings use frontmatterFields even when the model has no rules', function () {
    $root = createCheckMarkdownFixture('unknown-field-article.md');

    try {
        $exitCode = Artisan::call('fusion:check', [
            '--dir' => $root,
            '--validate' => true,
            '--model' => ArticleWithoutFrontmatterRules::class,
            '--warn-unknown-fields' => true,
            '--format' => 'json',
        ]);
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        expect($exitCode)->toBe(0)
            ->and($report['summary']['valid'])->toBe(1)
            ->and($report['summary']['errors'])->toBe(0)
            ->and($report['summary']['warnings'])->toBe(2)
            ->and(array_column($report['findings'], 'rule'))->toBe([
                'frontmatter.unknown_field',
                'frontmatter.rules_not_defined',
            ]);
    } finally {
        removeTemporaryTestDirectory($root);
    }
});

test('unknown Frontmatter fields are reported as opt-in warnings', function () {
    $root = createCheckMarkdownFixture('unknown-field-article.md');

    try {
        $exitCode = Artisan::call('fusion:check', [
            '--dir' => $root,
            '--validate' => true,
            '--model' => ArticleWithFrontmatterRules::class,
            '--format' => 'json',
        ]);
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        expect($exitCode)->toBe(0)
            ->and($report['summary']['warnings'])->toBe(0);

        $exitCode = Artisan::call('fusion:check', [
            '--dir' => $root,
            '--validate' => true,
            '--model' => ArticleWithFrontmatterRules::class,
            '--warn-unknown-fields' => true,
            '--format' => 'json',
        ]);
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        expect($exitCode)->toBe(0)
            ->and($report['status'])->toBe('ok')
            ->and($report['summary']['valid'])->toBe(1)
            ->and($report['summary']['errors'])->toBe(0)
            ->and($report['summary']['warnings'])->toBe(1)
            ->and($report['findings'][0]['severity'])->toBe('warning')
            ->and($report['findings'][0]['rule'])->toBe('frontmatter.unknown_field')
            ->and($report['findings'][0]['message'])->toContain('publised', 'frontmatterFields()');
    } finally {
        removeTemporaryTestDirectory($root);
    }
});

test('unknown Frontmatter field warnings require a model', function () {
    $root = createCheckMarkdownFixture('page.md');

    try {
        $exitCode = Artisan::call('fusion:check', [
            '--dir' => $root,
            '--validate' => true,
            '--warn-unknown-fields' => true,
            '--format' => 'json',
        ]);
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        expect($exitCode)->toBe(2)
            ->and($report['findings'][0]['rule'])->toBe('frontmatter.unknown_fields_model_required')
            ->and($report['findings'][0]['message'])->toContain('--model');
    } finally {
        removeTemporaryTestDirectory($root);
    }
});

test('interactive check guides the user through deep model validation', function () {
    $root = createCheckMarkdownFixture('unknown-field-article.md');
    $appPath = sys_get_temp_dir() . '/fusion-interactive-models-' . bin2hex(random_bytes(8));
    $modelsPath = $appPath . '/Models';
    mkdir($modelsPath, 0777, true);
    file_put_contents($modelsPath . '/InteractiveArticle.php', "<?php\nnamespace App\\Models;\nclass InteractiveArticle {}\n");
    $originalAppPath = app()->path();
    app()->useAppPath($appPath);
    class_alias(ArticleWithFrontmatterRules::class, 'App\\Models\\InteractiveArticle');

    try {
        $this->artisan('fusion:check', [
            '--dir' => $root,
            '--interactive' => true,
        ])
            ->expectsChoice(
                'What would you like Fusion to check?',
                'Deep validation (parse Frontmatter and render Markdown)',
                [
                    'Quick inventory (list Frontmatter fields)',
                    'Deep validation (parse Frontmatter and render Markdown)',
                ],
            )
            ->expectsQuestion(
                'Search for a model to use for Frontmatter validation',
                'App\\Models\\InteractiveArticle',
            )
            ->expectsChoice(
                'Search for a model to use for Frontmatter validation',
                'App\\Models\\InteractiveArticle',
                [
                    'App\\Models\\InteractiveArticle' => 'App\\Models\\InteractiveArticle',
                ],
            )
            ->expectsConfirmation('Warn about Frontmatter fields not listed in frontmatterFields()?', 'yes')
            ->expectsConfirmation('Include valid files in the validation output?', 'yes')
            ->expectsOutputToContain('Validation: 1 file(s), 1 valid, 0 invalid, 0 error(s), 1 warning(s).')
            ->assertExitCode(0);
    } finally {
        removeTemporaryTestDirectory($root);
        unlink($modelsPath . '/InteractiveArticle.php');
        rmdir($modelsPath);
        rmdir($appPath);
        app()->useAppPath($originalAppPath);
    }
});

test('interactive check rejects JSON output to keep prompts out of machine-readable reports', function () {
    $this->artisan('fusion:check', [
        '--interactive' => true,
        '--format' => 'json',
    ])
        ->expectsOutputToContain('Interactive checking cannot be combined with --format=json')
        ->assertExitCode(2);
});
