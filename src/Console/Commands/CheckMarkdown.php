<?php

declare(strict_types=1);

namespace HiFolks\Fusion\Console\Commands;

use HiFolks\Fusion\Fusion;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Spatie\YamlFrontMatter\YamlFrontMatter;
use Tempest\Highlight\CommonMark\HighlightExtension;
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
    {--include-valid : Include valid file records in validation output }
    {--changed : Inspect only changed Markdown files reported by Git }
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
        $format = strtolower((string) $this->option('format'));
        if (! in_array($format, ['table', 'json'], true)) {
            $this->error('Unsupported output format. Use "table" or "json".');

            return Command::INVALID;
        }

        $json = $format === 'json';
        $contentDirectory = Fusion::getContentDirectory($this->option("dir"));

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

        $changedFiles = null;
        if ($this->option('changed')) {
            try {
                $changedFiles = $this->changedMarkdownFiles($contentDirectory);
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

        $report = $this->inspectContent($contentDirectory, (bool) $this->option('validate'), $changedFiles);
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

    /**
     * @param  array<int, string>|null  $changedFiles
     * @return array{status: string, summary: array{files: int, valid: int, invalid: int, errors: int, warnings: int}, files: array<int, array{file: string, fields: array<int, string>, valid: bool}>, findings: array<int, array{file: string, severity: string, rule: string, message: string}>}
     */
    private function inspectContent(string $contentDirectory, bool $validate, ?array $changedFiles = null): array
    {
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

            try {
                $document = YamlFrontMatter::parse($filesystem->get($file->getPathname()));
                $fields = array_map(strval(...), array_keys($document->matter()));
            } catch (Throwable $exception) {
                $valid = false;
                $findings[] = [
                    'file' => $relativePath,
                    'severity' => 'error',
                    'rule' => 'frontmatter.parse',
                    'message' => $exception->getMessage(),
                ];
            }

            if ($validate && $valid) {
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
     * @return array<int, string>
     */
    private function changedMarkdownFiles(string $contentDirectory): array
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

        $statusResult = Process::path($repositoryPath)->run([
            'git', 'status', '--porcelain=v1', '-z', '--untracked-files=all', '--', $relativeContentPath,
        ]);

        if (! $statusResult->successful()) {
            throw new RuntimeException('Git could not determine which Markdown files have changed.');
        }

        $entries = explode("\0", $statusResult->output());
        $changedFiles = [];
        $counter = count($entries);

        for ($index = 0; $index < $counter; ++$index) {
            $entry = $entries[$index];
            if (strlen($entry) < 4) {
                continue;
            }

            $status = substr($entry, 0, 2);
            $relativePath = substr($entry, 3);
            if (str_contains($status, 'R') || str_contains($status, 'C')) {
                ++$index;
            }

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
     * @param  array<int, array{file: string, severity: string, rule: string, message: string}>  $findings
     * @return array{status: string, summary: array{files: int, valid: int, invalid: int, errors: int, warnings: int}, files: array<int, array{file: string, fields: array<int, string>, valid: bool}>, findings: array<int, array{file: string, severity: string, rule: string, message: string}>}
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
     * @param  array{summary: array{files: int, valid: int, invalid: int, errors: int, warnings: int}, files: array<int, array{file: string, fields: array<int, string>, valid: bool}>, findings: array<int, array{file: string, severity: string, rule: string, message: string}>}  $report
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

            $message = sprintf('%s: %s', $finding['file'], $finding['message']);
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
