<?php

declare(strict_types=1);

namespace HiFolks\Fusion\Models;

use HiFolks\Fusion\Traits\FusionModelTrait;

class Page extends FusionBaseModel
{
    use FusionModelTrait;

    public function frontmatterFields(): array
    {
        return [
            'title', 'description',
        ];
    }
}
