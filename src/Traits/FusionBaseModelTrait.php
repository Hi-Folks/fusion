<?php

declare(strict_types=1);

namespace HiFolks\Fusion\Traits;

use HiFolks\Fusion\ContentCache;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Exception\AlreadyInitializedException;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Spatie\YamlFrontMatter\YamlFrontMatter;
use Sushi\Sushi;
use Symfony\Component\Yaml\Exception\ParseException;
use Tempest\Highlight\CommonMark\HighlightExtension;

trait FusionBaseModelTrait
{
    use Sushi;

    public function getResourceFolder(): string
    {
        $folderName = str_replace(
            ['App\\Models\\', 'HiFolks\\Fusion\\Models\\'],
            '',
            static::class,
        );
        $folderName = Str::snake($folderName);

        $configuredDirectory = config('fusion.content_directory');
        if (! is_string($configuredDirectory) || $configuredDirectory === '') {
            $resourceDirectory = resource_path('content');
        } elseif ($this->isAbsolutePath($configuredDirectory)) {
            $resourceDirectory = $configuredDirectory;
        } else {
            $resourceDirectory = base_path($configuredDirectory);
        }

        return rtrim($resourceDirectory, '/\\') . DIRECTORY_SEPARATOR . $folderName;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getTable()
    {
        return parent::getTable();
    }

    /**
     * @return array<int, string>
     */
    public function frontmatterFields(): array
    {
        return [
            'title',
        ];
    }

    /**
     * Return the list of the field name managed by the base class
     *
     * @return array<int, string>
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

    /**
     * @return array<int, mixed>
     *
     * @throws AlreadyInitializedException
     * @throws BindingResolutionException
     * @throws FileNotFoundException
     * @throws ParseException
     * @throws CommonMarkException
     */
    public function getRows(): array
    {
        return $this->getFrontmatterRows(
            $this->frontmatterFields(),
        );
    }

    /**
     * @param  array<int, string>  $columns
     * @return array<int, mixed>
     */
    public function getFrontmatterRows(array $columns = []): array
    {
        return app(ContentCache::class)->rememberRows(
            static::class,
            $this->getResourceFolder(),
            $columns,
            fn(): array => $this->resolveFrontmatterRows($columns),
        );
    }

    /**
     * @param  array<int, string>  $columns
     * @return array<int, mixed>
     *
     * @throws BindingResolutionException
     * @throws FileNotFoundException
     * @throws ParseException
     * @throws CommonMarkException
     */
    private function resolveFrontmatterRows(array $columns): array
    {
        $environment = new Environment()
            ->addExtension(new CommonMarkCoreExtension())
            ->addExtension(new HighlightExtension());

        $converter = new MarkdownConverter($environment);
        $filesystem = new Filesystem();
        $markdowns = [];

        foreach (File::allFiles($this->getResourceFolder()) as $file) {
            $slug = $file->getFilenameWithoutExtension();
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
                $value = $object->matter($column);
                $row[$column] = is_array($value) ? json_encode($value) : $value;
            }

            $markdowns[] = $row;
        }

        return $markdowns;
    }
}
