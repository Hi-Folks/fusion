<?php

declare(strict_types=1);

/*
 * You can place your custom package configuration in here.
 */
return [
    'content_directory' => env('FUSION_CONTENT_DIRECTORY'),
    // Lifetime of Fusion's cached content rows, in seconds.
    // Configure the cache store in Laravel's config/cache.php as usual.
    'cache_ttl' => env('FUSION_CACHE_TTL', 3600),
];
