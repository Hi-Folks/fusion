<?php

declare(strict_types=1);

namespace HiFolks\Fusion\Console\Commands;

use HiFolks\Fusion\Fusion;
use HiFolks\Fusion\Output;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

use function Laravel\Prompts\confirm;

class StartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fusion:start
                            {--dir= : The directory containing the content files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect the content directory and suggest how to get started with Fusion';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $output = new Output($this->output);

        $output->title('Welcome to Fusion');

        $directoryOption = $this->option('dir');
        $contentDirectory = Fusion::getContentDirectory(
            is_string($directoryOption) ? $directoryOption : null,
        );

        $output->p('Checking the content directory:');
        $output->sectionTitle($contentDirectory);

        if (! is_dir($contentDirectory)) {
            $confirmed = confirm(
                label: "The content directory doesn't exist. Create it?",
                default: false,
                yes: 'Yes, create it',
                no: 'No, leave it unchanged',
                hint: 'Fusion stores Markdown content in this directory.',
            );

            if (! $confirmed) {
                $output->line('No directory was created. Run this command again when you are ready.');

                return Command::SUCCESS;
            }

            $filesystem = new Filesystem();
            $filesystem->makeDirectory($contentDirectory, 0755, true, true);

            if (! is_dir($contentDirectory)) {
                $output->line('Unable to create the content directory: ' . $contentDirectory);

                return Command::FAILURE;
            }

            $output->line('Content directory created.');
        }

        $output->labelValue(
            'Directory exists?',
            'YES',
        );

        $files = File::files($contentDirectory);
        $directories = File::directories($contentDirectory);

        foreach ($files as $file) {
            $output->labelValue('File', $file->getRelativePathName());
        }

        foreach ($directories as $directory) {
            $output->labelValue('Content type', basename((string) $directory));
        }

        $output->labelValue('Files', count($files));
        $output->labelValue('Content types', count($directories));

        if ($directories === []) {
            $output->line(sprintf('Create a content-type directory, for example: %s/article', $contentDirectory));
            $output->line('Then add Markdown files to it and run this command again.');

            return Command::SUCCESS;
        }

        $markdownFiles = array_filter(
            File::allFiles($contentDirectory),
            static fn(SplFileInfo $file): bool => strtolower($file->getExtension()) === 'md',
        );

        if ($markdownFiles === []) {
            $output->line('No Markdown files were found. Add a .md file to a content-type directory.');

            return Command::SUCCESS;
        }

        $output->labelValue('Markdown files', count($markdownFiles));
        $output->line(sprintf('Next: php artisan fusion:check --dir="%s"', $contentDirectory));
        $output->line('Generate a model with: php artisan fusion:sync-model --path="<content-type-directory>" --create-model');

        return Command::SUCCESS;
    }
}
