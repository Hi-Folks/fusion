<?php

declare(strict_types=1);

namespace HiFolks\Fusion\Traits;

use Sushi\Sushi;

trait FusionModelTrait
{
    use Sushi;

    public function getRows(): array
    {
        return parent::getRows();
    }
}
