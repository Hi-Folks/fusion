<?php

namespace HiFolks\Fusion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\MarkdownConverter;
use Spatie\CommonMarkHighlighter\FencedCodeRenderer;
use Spatie\CommonMarkHighlighter\IndentedCodeRenderer;
use Spatie\YamlFrontMatter\YamlFrontMatter;

abstract class FusionBaseModel extends Model
{
    public function getResourceFolder(): string
    {

        $folderName = str_replace(
            'App\\Models\\',
            '',
            static::class
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

    /**
     * Return the list of the field name managed by the base class
     */
    public static function frontmatterBaseFields(): array
    {
        return [
            'title',
            'slug',
            'body',
            'real_path',
            'relative_path_name',
        ];
    }

    public function getRows(): array
    {
        return $this->getFrontmatterRows(
            $this->frontmatterFields()
        );
    }

    /**
     * @return non-empty-array[]
     */
    public function getFrontmatterRows($columns = []): array
    {

        $environment = (new Environment())
            ->addExtension(new CommonMarkCoreExtension());

        //$environment->addExtension(new CommonMarkCoreExtension());
        $environment->addRenderer(FencedCode::class, new FencedCodeRenderer());
        $environment->addRenderer(IndentedCode::class, new IndentedCodeRenderer());

        $converter = new MarkdownConverter($environment);

        $filesystem = new FileSystem();
        $markdowns = [];

        foreach (File::allFiles(resource_path($this->getResourceFolder())) as $file) {
            // default slug as filename
            $slug = $file->getFilenameWithoutExtension();

            $filename = $file->getRelativePathName();
            $fileContent = $filesystem->get($file->getRealPath());

            $object = YamlFrontMatter::parse($fileContent);

            if (array_key_exists('slug', $object->matter())) {
                $slug = $object->matter('slug');
            }

            $row = [
                'slug' => $slug,
                'body' => $converter->convert($object->body()),
                'real_path' => $file->getRealPath(),
                'relative_path_name' => $file->getRelativePathname(),
            ];

            foreach ($columns as $column) {
                $row[$column] = is_array($object->matter($column)) ? json_encode($object->matter($column)) : $object->matter($column);

            }

            $markdowns[] = $row;
        }

        return $markdowns;
    }
}
