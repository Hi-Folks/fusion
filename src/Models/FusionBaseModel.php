<?php

namespace HiFolks\Fusion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Spatie\YamlFrontMatter\YamlFrontMatter;

abstract class FusionBaseModel extends Model
{
    public function getResourceFolder(): string
    {

        $folderName = str_replace(
            'App\\Models\\',
            '',
            get_called_class()
        );
        $folderName = Str::snake($folderName);

        return 'content'.DIRECTORY_SEPARATOR.$folderName;
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function frontmatterFields(): array
    {
        return [
            'title',
        ];
    }

    public function getRows()
    {
        return $this->getFrontmatterRows(
            $this->frontmatterFields()
        );
    }

    public function getFrontmatterRows($columns = [])
    {

        $environment = (new Environment())
            ->addExtension(new CommonMarkCoreExtension());
        $converter = new MarkdownConverter($environment);

        $filesystem = new FileSystem();
        $markdowns = [];

        foreach (File::allFiles(resource_path($this->getResourceFolder())) as $file) {
            $slug = $file->getFilenameWithoutExtension();

            $filename = $file->getRelativePathName();
            $fileContent = $filesystem->get($file->getRealPath());

            $object = YamlFrontMatter::parse($fileContent);

            $row = [
                'title' => $object->matter('title'),
                'label' => $object->matter('label'),
                'excerpt' => $object->matter('excerpt'),
                'date' => $object->matter('date') ? Carbon::createFromTimestamp($object->matter('date'))->format('Y-m-d') : null,
                'slug' => $slug,
                'body' => $converter->convert($object->body()),
                'real_path' => $file->getRealPath(),
                'relative_path_name' => $file->getRelativePathname(),
                'published' => $object->matter('published'),
                'highlight' => $object->matter('highlight'),

            ];

            foreach ($columns as $column) {
                $row[$column] = $object->matter($column);
            }

            $markdowns[] = $row;
        }

        return $markdowns;
    }
}
