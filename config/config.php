<?php

declare(strict_types=1);

/*
 * You can place your custom package configuration in here.
 */
return [
    // Root directory containing one subdirectory per model (e.g. Article => resources/content/article).
    // Relative paths resolve from the Laravel application base path; absolute paths are also supported.
    // When null or empty, Fusion uses resources/content.
    'content_directory' => env('FUSION_CONTENT_DIRECTORY'),
    // Lifetime of Fusion's cached content rows, in seconds.
    // Configure the cache store in Laravel's config/cache.php as usual.
    'cache_ttl' => env('FUSION_CACHE_TTL', 3600),
];
