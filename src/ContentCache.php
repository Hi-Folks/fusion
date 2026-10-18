<?php

declare(strict_types=1);

namespace HiFolks\Fusion;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\File;

final readonly class ContentCache
{
    public function __construct(
        private Repository $cache,
        private int $ttl = 3600,
    ) {}

    /**
     * @param  array<int, string>  $columns
     * @param  Closure(): array<int, mixed>  $resolveRows
     * @return array<int, mixed>
     */
    public function rememberRows(
        string $modelClass,
        string $contentDirectory,
        array $columns,
        Closure $resolveRows,
    ): array {
        $files = File::allFiles($contentDirectory);
        usort(
            $files,
            static fn($left, $right): int => $left->getRelativePathname() <=> $right->getRelativePathname(),
        );

        $contentFingerprint = [];
        foreach ($files as $file) {
            $contentFingerprint[] = [
                $file->getRelativePathname(),
                hash_file('sha256', $file->getRealPath()),
            ];
        }

        $key = 'fusion:rows:' . hash(
            'sha256',
            serialize([
                $modelClass,
                $contentDirectory,
                $columns,
                $contentFingerprint,
            ]),
        );

        return $this->cache->remember($key, $this->ttl, $resolveRows);
    }
}
