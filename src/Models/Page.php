<?php

declare(strict_types=1);

namespace HiFolks\Fusion\Models;

class Page extends FusionBaseModel
{
    public function frontmatterFields(): array
    {
        return [
            'title', 'description',
        ];
    }
}
