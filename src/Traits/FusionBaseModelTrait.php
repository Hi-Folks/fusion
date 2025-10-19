<?php

declare(strict_types=1);

namespace HiFolks\Fusion\Traits;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Exception\AlreadyInitializedException;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\MarkdownConverter;
use Spatie\CommonMarkHighlighter\FencedCodeRenderer;
use Spatie\CommonMarkHighlighter\IndentedCodeRenderer;
use Spatie\YamlFrontMatter\YamlFrontMatter;
use Sushi\Sushi;
use Symfony\Component\Yaml\Exception\ParseException;

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

        $resourceDirectory = resource_path('content' . DIRECTORY_SEPARATOR);
        if (!is_null(config('fusion.content_directory'))) {
            $resourceDirectory = __DIR__ . '/../../' . config('fusion.content_directory');
        }

        return $resourceDirectory . $folderName;
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
        $environment = (new Environment())
            ->addExtension(new CommonMarkCoreExtension());

        $environment->addRenderer(FencedCode::class, new FencedCodeRenderer());
        $environment->addRenderer(IndentedCode::class, new IndentedCodeRenderer());

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
