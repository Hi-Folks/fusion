<?php

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
