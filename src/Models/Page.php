<?php

declare(strict_types=1);

namespace HiFolks\Fusion\Models;

use HiFolks\Fusion\Traits\FusionBaseModelTrait;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use FusionBaseModelTrait;

    /**
     * Return the list of the field names managed by the Page Model class
     *
     * @return array<int, string>
     */
    public function frontmatterFields(): array
    {
        return [
            'title', 'description',
        ];
    }
}
