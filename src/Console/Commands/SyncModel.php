<?php

namespace HiFolks\Fusion\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class SyncModel extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fusion:sync-model
                            {--path= : the directory of the Markdown files}
                            {--create-model : Whether the Model should be created}
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync the Model file from Markdown definition';

    /**
     * The auto-detected model name retrieved from the markdown folder.
     * For example for resources/content/article the model name
     * will be the folder base name in PascalCase format so Article
     *
     * @var string
     */
    protected $modelName = '';

    /**
     * The auto-detected frontmatter field names retrieved
     * by parsing all the markdown files in a specific folder
     * The format of the field list is in string, useful to be used
     * in the model for example:
     * '"title", "description", "published"'
     *
     * @var string
     */
    protected $frontmatterFields = '';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {

        $markdownPath = $this->option('path');
        $createModel = $this->option('create-model');

        if (is_null($markdownPath)) {
            $markdownPath = resource_path('content/article');
        }

        $this->info('Parsing the Markdown Directory: '.$markdownPath);
        $modelName = basename($markdownPath);
        $this->info('Folder Markdown Name: '.$modelName);

        if (! is_dir($markdownPath)) {
            $this->error(sprintf('The Folder %s does not exist!', $markdownPath));

            return Command::INVALID;
        }

        $filesystem = new FileSystem();

        $collectedFields = [];
        foreach (File::files($markdownPath) as $file) {
            $slug = $file->getFilenameWithoutExtension();
            $extension = $file->getExtension();

            $filename = $file->getRelativePathName();
            $fileContent = $filesystem->get($file->getRealPath());

            $object = YamlFrontMatter::parse($fileContent);
            $collectedFields = array_unique(
                array_merge(
                    $collectedFields,
                    array_keys($object->matter())
                )
            );

        }

        $this->modelName = Str::studly($modelName);
        $this->info('Model Name : '.$this->modelName.'');
        $this->frontmatterFields = '"'.implode('","', $collectedFields).'"';
        $this->info('In the frontmatterFields method you have to return: '.
            $this->frontmatterFields.
            '');

        if ($createModel) {
            parent::handle();
        }

        return Command::SUCCESS;

    }

    protected function getNameInput()
    {
        return '\\App\\Models\\'.$this->modelName;
    }

    protected function getStub()
    {
        return realpath(
            __DIR__.
            '/../../..'.
            '/stubs/app/Models/BasicFusionTemplateModel.stub'
        );
    }

    protected function replaceClass($stub, $name)
    {
        $stub = str_replace(
            '{{ fields }}',
            $this->frontmatterFields,
            $stub
        );

        return parent::replaceClass($stub, $name);
    }
}
