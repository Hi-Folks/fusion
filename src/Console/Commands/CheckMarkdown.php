<?php

namespace HiFolks\Fusion\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class CheckMarkdown extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fusion:check {--dir=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the Markdown files';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Parsing the Markdown Files');
        $filesystem = new FileSystem();
        $contentDirectory = $this->option('dir');
        if (is_null($contentDirectory)) {
            $contentDirectory = resource_path('content');
        }

        foreach (File::directories($contentDirectory) as $directory) {
            $this->info('Directory: '.$directory);
            foreach (File::files($directory) as $file) {
                $slug = $file->getFilenameWithoutExtension();
                $extension = $file->getExtension();

                $filename = $file->getRelativePathName();
                $fileContent = $filesystem->get($file->getRealPath());

                $object = YamlFrontMatter::parse($fileContent);
                $this->components->twoColumnDetail($extension.' - '.$filename, implode('; ', array_keys($object->matter())));
                //dd($object->matter());
            }
        }
    }
}
