<?php

declare(strict_types=1);

namespace HiFolks\Fusion;

class Fusion
{
    public static function getContentDirectory(?string $directory = null): string
    {
        return $directory === null || $directory === ''
            ? resource_path('content')
            : $directory;
    }
}
