<?php

declare(strict_types=1);

namespace HiFolks\Fusion\Traits;

use Sushi\Sushi;

trait SushiModelTrait
{
    use Sushi;

    public function getRows(): array
    {
        return parent::getRows();
    }
}
