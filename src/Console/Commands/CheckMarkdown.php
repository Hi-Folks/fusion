<?php

namespace HiFolks\Fusion\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class CheckMarkdown extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fusion:check
    {--dir= : the directory of the Markdown files }';

    /**
     * @var string
     */
    protected $description = 'It shows the list of the frontmatter fields of each Markdown file found';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {

        $this->output->title('Parsing the Markdown Files');
        $filesystem = new FileSystem();
        $contentDirectory = $this->option('dir');
        if (is_null($contentDirectory)) {
            $contentDirectory = resource_path('content');
        }

        if (! is_dir($contentDirectory)) {
            $this->warn(' The directory `'.$contentDirectory."` doesn't exist.");
            $this->newLine();
            $this->info(' You should create the directory `'.$contentDirectory.'` and start creating Markdown files in that directory.');
            $this->newLine();
            $this->output->listing(
                [
                    'Create the `'.$contentDirectory.'` directory;',
                    'Create the content type directory, for example `'.$contentDirectory.'/article`',
                    'Create the Markdown files in the new directory;',
                    'Execute the `php artisan fusion:sync` command for generating the model.',
                ]
            );

            return Command::INVALID;
        }

        foreach (File::directories($contentDirectory) as $directory) {
            $this->components->twoColumnDetail('<info>Directory</info>', sprintf('<info>%s</info>', $directory));
            //$this->output->->info('Directory: '.$directory);
            $numberNoMarkdown = 0;
            $numberMarkdown = 0;
            foreach (File::files($directory) as $file) {
                $slug = $file->getFilenameWithoutExtension();
                $extension = $file->getExtension();

                $filename = $file->getRelativePathName();
                $fileContent = $filesystem->get($file->getRealPath());

                $object = YamlFrontMatter::parse($fileContent);
                if ($extension == 'md') {
                    $numberMarkdown++;
                    $this->components->twoColumnDetail(' - '.$filename, implode('; ', array_keys($object->matter())));
                } else {
                    $numberNoMarkdown++;
                }
            }

            if ($numberMarkdown == 0) {
                $this->warn('No Markdown files in '.$directory);
            }

            if ($numberNoMarkdown > 0) {
                $this->warn(sprintf('Found %d files, with a no markdown extensions', $numberNoMarkdown));
            }

            $this->output->newLine();
        }

        return Command::SUCCESS;
    }
}
