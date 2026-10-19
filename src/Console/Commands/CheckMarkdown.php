<?php

declare(strict_types=1);

namespace HiFolks\Fusion\Console\Commands;

use HiFolks\Fusion\Fusion;
use HiFolks\Fusion\Traits\FusionBaseModelTrait;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Validator;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Spatie\YamlFrontMatter\YamlFrontMatter;
use Tempest\Highlight\CommonMark\HighlightExtension;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Throwable;

class CheckMarkdown extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fusion:check
    {--dir= : the directory of the Markdown files }
    {--validate : Also render Markdown bodies to check parsing and highlighting }
    {--model= : Validate Frontmatter using the rules defined by a Fusion model (requires --validate) }
    {--warn-unknown-fields : Warn about Frontmatter keys missing from the model frontmatterFields() (requires --model and --validate) }
    {--include-valid : Include valid file records in validation output }
    {--changed : Inspect only changed Markdown files reported by Git }
    {--base= : Compare changed files with a Git ref (requires --changed) }
    {--interactive : Choose check options using interactive prompts }
    {--format=table : Output format: table or json }';

    /**
     * @var string
     */
    protected $description = 'Inspect Markdown files and optionally validate their frontmatter and rendered bodies';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('interactive')) {
            if (strtolower((string) $this->option('format')) === 'json') {
                $this->error('Interactive checking cannot be combined with --format=json. Use a non-interactive command for JSON output.');

                return Command::INVALID;
            }

            if (! $this->input->isInteractive()) {
                $this->error('Interactive checking requires an interactive terminal.');

                return Command::INVALID;
            }

            $this->configureInteractiveOptions();
        }

        $format = strtolower((string) $this->option('format'));
        if (! in_array($format, ['table', 'json'], true)) {
            $this->error('Unsupported output format. Use "table" or "json".');

            return Command::INVALID;
        }

        $json = $format === 'json';
        $contentDirectory = Fusion::getContentDirectory($this->option("dir"));
        $baseOption = $this->option('base');
        $baseRef = is_string($baseOption) && $baseOption !== '' ? $baseOption : null;

        if ($baseRef !== null && ! $this->option('changed')) {
            return $this->invalidReport($json, '--base', 'The --base option requires --changed.', 'git.base_requires_changed');
        }

        if (! is_dir($contentDirectory)) {
            if ($json) {
                $this->writeJson($this->report(
                    [],
                    [[
                        'file' => $contentDirectory,
                        'severity' => 'error',
                        'rule' => 'content.directory_not_found',
                        'message' => 'The content directory does not exist.',
                    ]],
                ));
            } else {
                $this->warn("The directory `{$contentDirectory}` doesn't exist.");
                $this->info('Create it, add content type directories and Markdown files, then run `php artisan fusion:sync-model`.');
            }

            return Command::INVALID;
        }

        $frontmatterRules = null;
        $declaredFrontmatterFields = null;
        $modelOption = $this->option('model');
        $warnUnknownFields = (bool) $this->option('warn-unknown-fields');
        if ($warnUnknownFields && (! is_string($modelOption) || $modelOption === '')) {
            return $this->invalidReport(
                $json,
                '--warn-unknown-fields',
                'The --warn-unknown-fields option requires --model and --validate.',
                'frontmatter.unknown_fields_model_required',
            );
        }

        if (is_string($modelOption) && $modelOption !== '') {
            if (! $this->option('validate')) {
                return $this->invalidReport(
                    $json,
                    $modelOption,
                    'The --model option requires --validate.',
                );
            }

            try {
                $modelSchema = $this->schemaForModel($modelOption, $warnUnknownFields);
                $frontmatterRules = $modelSchema['rules'];
                $declaredFrontmatterFields = $warnUnknownFields
                    ? array_fill_keys([...$modelSchema['fields'], 'slug'], true)
                    : null;
            } catch (RuntimeException $exception) {
                return $this->invalidReport($json, $modelOption, $exception->getMessage());
            }
        }

        $changedFiles = null;
        if ($this->option('changed')) {
            try {
                $changedFiles = $this->changedMarkdownFiles($contentDirectory, $baseRef);
            } catch (RuntimeException $exception) {
                $report = $this->report([], [[
                    'file' => $contentDirectory,
                    'severity' => 'error',
                    'rule' => 'git.changed_files_unavailable',
                    'message' => $exception->getMessage(),
                ]]);

                if ($json) {
                    $this->writeJson($report);
                } else {
                    $this->error($exception->getMessage());
                }

                return Command::INVALID;
            }
        }

        $report = $this->inspectContent(
            $contentDirectory,
            (bool) $this->option('validate'),
            $changedFiles,
            $frontmatterRules,
            $declaredFrontmatterFields,
        );
        if (is_string($modelOption) && $modelOption !== '' && $frontmatterRules === null) {
            $report['findings'][] = [
                'file' => null,
                'severity' => 'warning',
                'rule' => 'frontmatter.rules_not_defined',
                'message' => sprintf(
                    'Model-based Frontmatter validation was skipped because "%s" does not define the optional frontmatterRules() method.',
                    $modelOption,
                ),
            ];
            ++$report['summary']['warnings'];
        }

        if ($this->option('validate') && ! $this->option('include-valid')) {
            $report['files'] = array_values(array_filter(
                $report['files'],
                static fn(array $file): bool => ! $file['valid'],
            ));
        }

        if ($json) {
            $this->writeJson($report);
        } else {
            $this->writeTable($report, $contentDirectory, (bool) $this->option('changed'));
        }

        return $report['summary']['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function configureInteractiveOptions(): void
    {
        $quickCheck = 'Quick inventory (list Frontmatter fields)';
        $deepCheck = 'Deep validation (parse Frontmatter and render Markdown)';
        $defaultCheck = $this->option('validate') ? $deepCheck : $quickCheck;
        $checkType = \Laravel\Prompts\select(
            label: 'What would you like Fusion to check?',
            options: [$quickCheck, $deepCheck],
            default: $defaultCheck,
            hint: 'Use the arrow keys to choose an option.',
        );
        $validate = $checkType === $deepCheck;
        $defaultModel = (string) ($this->option('model') ?? '');
        $defaultWarnUnknownFields = (bool) $this->option('warn-unknown-fields');
        $defaultIncludeValid = (bool) $this->option('include-valid');

        $this->input->setOption('validate', $validate);
        $this->input->setOption('model', '');
        $this->input->setOption('warn-unknown-fields', false);
        $this->input->setOption('include-valid', false);

        if (! $validate) {
            return;
        }

        $enterManually = 'Enter a model class manually';
        $skipModel = 'Skip model-based Frontmatter validation';
        $discoveredModels = CheckModel::discoverModelClasses(app_path('Models'));
        $modelChoices = $discoveredModels === [] ? [] : array_combine($discoveredModels, $discoveredModels);
        if ($defaultModel !== '' && ! array_key_exists($defaultModel, $modelChoices)) {
            $modelChoices[$defaultModel] = $defaultModel;
        }

        $modelChoices[$enterManually] = $enterManually;
        $modelChoices[$skipModel] = $skipModel;

        $this->line('A model is optional. Without frontmatterRules(), Fusion will check parsing and rendering but skip schema rules.');
        if (count($modelChoices) === 2) {
            $this->line('No model classes were found in ' . app_path('Models') . '.');
        }

        $selectedModel = $defaultModel !== ''
            ? $defaultModel
            : \Laravel\Prompts\search(
                label: 'Search for a model to use for Frontmatter validation',
                options: static fn(string $query): array => array_filter(
                    $modelChoices,
                    static fn(string $value, string|int $name): bool => $query === ''
                        || str_contains(strtolower((string) $name), strtolower($query)),
                    ARRAY_FILTER_USE_BOTH,
                ),
                placeholder: 'Type a model name or choose an action',
                hint: 'Model names are fully qualified class names.',
            );
        $model = $selectedModel === $enterManually
            ? trim((string) $this->ask('Fusion model class (for example, "App\\Models\\Article")'))
            : ($selectedModel === $skipModel ? '' : $selectedModel);
        $this->input->setOption('model', $model);

        if ($model === '') {
            return;
        }

        $warnUnknownFields = \Laravel\Prompts\confirm(
            label: 'Warn about Frontmatter fields not listed in frontmatterFields()?',
            default: $defaultWarnUnknownFields,
        );
        $this->input->setOption('warn-unknown-fields', $warnUnknownFields);
        $this->input->setOption('include-valid', \Laravel\Prompts\confirm(
            label: 'Include valid files in the validation output?',
            default: $defaultIncludeValid,
        ));
    }

    /**
     * @param  array<int, string>|null  $changedFiles
     * @param  array<array-key, mixed>|null  $frontmatterRules
     * @param  array<string, bool>|null  $declaredFrontmatterFields
     * @return array{status: string, summary: array{files: int, valid: int, invalid: int, errors: int, warnings: int}, files: array<int, array{file: string, fields: array<int, string>, valid: bool}>, findings: array<int, array{file: string|null, severity: string, rule: string, message: string}>}
     */
    private function inspectContent(
        string $contentDirectory,
        bool $validate,
        ?array $changedFiles = null,
        ?array $frontmatterRules = null,
        ?array $declaredFrontmatterFields = null,
    ): array {
        $filesystem = new Filesystem();
        $files = File::allFiles($contentDirectory);
        if ($changedFiles !== null) {
            $changedFiles = array_fill_keys($changedFiles, true);
            $files = array_filter($files, static function (\Symfony\Component\Finder\SplFileInfo $file) use ($changedFiles): bool {
                $path = realpath($file->getPathname());

                return $path !== false && isset($changedFiles[$path]);
            });
        }

        usort($files, static fn($left, $right): int => $left->getRelativePathname() <=> $right->getRelativePathname());

        $inspectedFiles = [];
        $findings = [];
        $markdownByDirectory = [];
        $converter = $validate ? $this->markdownConverter() : null;

        foreach ($files as $file) {
            $relativePath = substr($file->getPathname(), strlen(rtrim($contentDirectory, DIRECTORY_SEPARATOR)) + 1);
            $directory = $file->getRelativePath();
            $extension = strtolower($file->getExtension());

            if ($extension !== 'md') {
                $findings[] = [
                    'file' => $relativePath,
                    'severity' => 'warning',
                    'rule' => 'file.not_markdown',
                    'message' => 'File does not have a .md extension and was skipped.',
                ];
                continue;
            }

            $markdownByDirectory[$directory] = ($markdownByDirectory[$directory] ?? 0) + 1;
            $fields = [];
            $valid = true;
            $parsed = false;

            try {
                $document = YamlFrontMatter::parse($filesystem->get($file->getPathname()));
                $fields = array_map(strval(...), array_keys($document->matter()));
                $parsed = true;
            } catch (Throwable $exception) {
                $valid = false;
                $findings[] = [
                    'file' => $relativePath,
                    'severity' => 'error',
                    'rule' => 'frontmatter.parse',
                    'message' => $exception->getMessage(),
                ];
            }

            if ($validate && $parsed) {
                if ($declaredFrontmatterFields !== null) {
                    foreach (array_keys($document->matter()) as $field) {
                        if (! is_string($field) || isset($declaredFrontmatterFields[$field])) {
                            continue;
                        }

                        $findings[] = [
                            'file' => $relativePath,
                            'severity' => 'warning',
                            'rule' => 'frontmatter.unknown_field',
                            'message' => sprintf(
                                'Frontmatter field "%s" is not listed in the selected model\'s frontmatterFields(). Add it there or correct the field name.',
                                $field,
                            ),
                        ];
                    }
                }

                if ($frontmatterRules !== null) {
                    $validator = Validator::make($document->matter(), $frontmatterRules);

                    if ($validator->fails()) {
                        $valid = false;
                        foreach ($validator->errors()->messages() as $field => $messages) {
                            foreach ($messages as $message) {
                                $findings[] = [
                                    'file' => $relativePath,
                                    'severity' => 'error',
                                    'rule' => 'frontmatter.validation',
                                    'message' => $field . ': ' . $message,
                                ];
                            }
                        }
                    }
                }

                try {
                    $converter->convert($document->body());
                } catch (Throwable $exception) {
                    $valid = false;
                    $findings[] = [
                        'file' => $relativePath,
                        'severity' => 'error',
                        'rule' => 'markdown.render',
                        'message' => $exception->getMessage(),
                    ];
                }
            }

            $inspectedFiles[] = [
                'file' => $relativePath,
                'fields' => $fields,
                'valid' => $valid,
            ];
        }

        if ($changedFiles === null) {
            foreach (File::directories($contentDirectory) as $directory) {
                $relativeDirectory = substr($directory, strlen(rtrim($contentDirectory, DIRECTORY_SEPARATOR)) + 1);
                if (! isset($markdownByDirectory[$relativeDirectory])) {
                    $findings[] = [
                        'file' => $relativeDirectory,
                        'severity' => 'warning',
                        'rule' => 'content.no_markdown_files',
                        'message' => 'Directory contains no Markdown files.',
                    ];
                }
            }
        }

        return $this->report($inspectedFiles, $findings);
    }

    /**
     * @return array{rules: array<array-key, mixed>|null, fields: array<int, string>}
     */
    private function schemaForModel(string $modelClass, bool $includeFields): array
    {
        if (! class_exists($modelClass)) {
            throw new RuntimeException(sprintf('The model class "%s" does not exist.', $modelClass));
        }

        if (! is_subclass_of($modelClass, Model::class)
            || ! in_array(FusionBaseModelTrait::class, class_uses_recursive($modelClass), true)) {
            throw new RuntimeException(sprintf('The model "%s" must be an Eloquent model using FusionBaseModelTrait.', $modelClass));
        }

        // Sushi initializes its in-memory table during normal model construction.
        // Rules are declarative, so avoid triggering that work just to read them.
        $model = new ReflectionClass($modelClass)->newInstanceWithoutConstructor();
        $rules = null;
        if (method_exists($model, 'frontmatterRules')) {
            if (! new ReflectionMethod($model, 'frontmatterRules')->isPublic()) {
                throw new RuntimeException(sprintf('The frontmatterRules() method on "%s" must be public.', $modelClass));
            }

            $rules = $model->frontmatterRules();
            if (! is_array($rules)) {
                throw new RuntimeException(sprintf('The frontmatterRules() method on "%s" must return an array.', $modelClass));
            }
        }

        $fields = [];
        if ($includeFields) {
            if (! method_exists($model, 'frontmatterFields')) {
                throw new RuntimeException(sprintf('The model "%s" must define a public frontmatterFields(): array method.', $modelClass));
            }

            $fieldsMethod = new ReflectionMethod($model, 'frontmatterFields');
            if (! $fieldsMethod->isPublic()) {
                throw new RuntimeException(sprintf('The frontmatterFields() method on "%s" must be public.', $modelClass));
            }

            $fields = $fieldsMethod->invoke($model);
            if (! is_array($fields) || array_any($fields, static fn(mixed $field): bool => ! is_string($field))) {
                throw new RuntimeException(sprintf('The frontmatterFields() method on "%s" must return an array of strings.', $modelClass));
            }
        }

        return ['rules' => $rules, 'fields' => $fields];
    }

    private function invalidReport(bool $json, string $file, string $message, string $rule = 'frontmatter.model_invalid'): int
    {
        $report = $this->report([], [[
            'file' => $file,
            'severity' => 'error',
            'rule' => $rule,
            'message' => $message,
        ]]);

        if ($json) {
            $this->writeJson($report);
        } else {
            $this->error($message);
        }

        return Command::INVALID;
    }

    /**
     * @return array<int, string>
     */
    private function changedMarkdownFiles(string $contentDirectory, ?string $baseRef = null): array
    {
        $contentPath = realpath($contentDirectory);
        if ($contentPath === false) {
            throw new RuntimeException('Changed-file filtering requires the content directory to be inside a Git repository.');
        }

        $repositoryResult = Process::path($contentPath)->run(['git', 'rev-parse', '--show-toplevel']);
        if (! $repositoryResult->successful()) {
            throw new RuntimeException('Changed-file filtering requires the content directory to be inside a Git repository.');
        }

        $repositoryPath = realpath(trim($repositoryResult->output()));
        if ($repositoryPath === false || ($contentPath !== $repositoryPath && ! str_starts_with($contentPath, $repositoryPath . DIRECTORY_SEPARATOR))) {
            throw new RuntimeException('Changed-file filtering requires the content directory to be inside a Git repository.');
        }

        $relativeContentPath = $contentPath === $repositoryPath
            ? '.'
            : str_replace(DIRECTORY_SEPARATOR, '/', substr($contentPath, strlen($repositoryPath) + 1));

        if ($baseRef !== null) {
            if (str_starts_with($baseRef, '-') || preg_match('/\\s/', $baseRef) === 1) {
                throw new RuntimeException('The --base option must be a valid Git ref, such as "origin/main".');
            }

            $changeResult = Process::path($repositoryPath)->run([
                'git', 'diff', '--name-only', '-z', '--diff-filter=ACMR', $baseRef . '...HEAD', '--', $relativeContentPath,
            ]);

            if (! $changeResult->successful()) {
                throw new RuntimeException(sprintf(
                    'Git could not compare Markdown files with base ref "%s". Ensure the ref exists locally (fetch it in CI).',
                    $baseRef,
                ));
            }

            $entries = explode("\0", $changeResult->output());
        } else {
            $changeResult = Process::path($repositoryPath)->run([
                'git', 'status', '--porcelain=v1', '-z', '--untracked-files=all', '--', $relativeContentPath,
            ]);

            if (! $changeResult->successful()) {
                throw new RuntimeException('Git could not determine which Markdown files have changed.');
            }

            $entries = explode("\0", $changeResult->output());
        }

        $changedFiles = [];
        $counter = count($entries);
        for ($index = 0; $index < $counter; ++$index) {
            $entry = $entries[$index];
            if ($entry === '') {
                continue;
            }

            if ($baseRef === null) {
                if (strlen($entry) < 4) {
                    continue;
                }

                $status = substr($entry, 0, 2);
                $entry = substr($entry, 3);
                if (str_contains($status, 'R') || str_contains($status, 'C')) {
                    ++$index;
                }
            }

            $relativePath = $entry;
            if (strtolower(pathinfo($relativePath, PATHINFO_EXTENSION)) !== 'md') {
                continue;
            }

            $absolutePath = realpath($repositoryPath . DIRECTORY_SEPARATOR . $relativePath);
            if ($absolutePath !== false && is_file($absolutePath)) {
                $changedFiles[] = $absolutePath;
            }
        }

        return array_values(array_unique($changedFiles));
    }

    private function markdownConverter(): MarkdownConverter
    {
        $environment = new Environment()
            ->addExtension(new CommonMarkCoreExtension())
            ->addExtension(new HighlightExtension());

        return new MarkdownConverter($environment);
    }

    /**
     * @param  array<int, array{file: string, fields: array<int, string>, valid: bool}>  $files
     * @param  array<int, array{file: string|null, severity: string, rule: string, message: string}>  $findings
     * @return array{status: string, summary: array{files: int, valid: int, invalid: int, errors: int, warnings: int}, files: array<int, array{file: string, fields: array<int, string>, valid: bool}>, findings: array<int, array{file: string|null, severity: string, rule: string, message: string}>}
     */
    private function report(array $files, array $findings): array
    {
        $errors = count(array_filter($findings, static fn(array $finding): bool => $finding['severity'] === 'error'));
        $warnings = count($findings) - $errors;
        $validFiles = count(array_filter($files, static fn(array $file): bool => $file['valid']));

        return [
            'status' => $errors > 0 ? 'failed' : 'ok',
            'summary' => [
                'files' => count($files),
                'valid' => $validFiles,
                'invalid' => count($files) - $validFiles,
                'errors' => $errors,
                'warnings' => $warnings,
            ],
            'files' => $files,
            'findings' => $findings,
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function writeJson(array $report): void
    {
        $this->output->writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array{summary: array{files: int, valid: int, invalid: int, errors: int, warnings: int}, files: array<int, array{file: string, fields: array<int, string>, valid: bool}>, findings: array<int, array{file: string|null, severity: string, rule: string, message: string}>}  $report
     */
    private function writeTable(array $report, string $contentDirectory, bool $changedOnly = false): void
    {
        $this->output->title('Parsing the Markdown Files');

        foreach (File::directories($contentDirectory) as $directory) {
            $relativeDirectory = substr($directory, strlen(rtrim($contentDirectory, DIRECTORY_SEPARATOR)) + 1);
            if ($changedOnly && ! array_any($report['files'], static fn(array $file): bool => str_starts_with($file['file'], $relativeDirectory . DIRECTORY_SEPARATOR))) {
                continue;
            }

            $this->components->twoColumnDetail('<info>Directory</info>', sprintf('<info>%s</info>', $relativeDirectory));

            foreach ($report['files'] as $file) {
                if (! str_starts_with($file['file'], $relativeDirectory . DIRECTORY_SEPARATOR)) {
                    continue;
                }

                $this->components->twoColumnDetail(' - ' . $file['file'], implode('; ', $file['fields']));
            }

            foreach ($report['findings'] as $finding) {
                if ($finding['file'] === $relativeDirectory && $finding['rule'] === 'content.no_markdown_files') {
                    $this->warn('No Markdown files in ' . $relativeDirectory);
                }
            }
        }

        foreach ($report['findings'] as $finding) {
            if ($finding['rule'] === 'content.no_markdown_files') {
                continue;
            }

            $message = $finding['file'] === null
                ? $finding['message']
                : sprintf('%s: %s', $finding['file'], $finding['message']);
            $finding['severity'] === 'error' ? $this->error($message) : $this->warn($message);
        }

        if ($this->option('validate')) {
            $this->line(sprintf(
                'Validation: %d file(s), %d valid, %d invalid, %d error(s), %d warning(s).',
                $report['summary']['files'],
                $report['summary']['valid'],
                $report['summary']['invalid'],
                $report['summary']['errors'],
                $report['summary']['warnings'],
            ));
        }

        if ($changedOnly && $report['summary']['files'] === 0) {
            $this->info('No changed Markdown files found.');
        }
    }
}
