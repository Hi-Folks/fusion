<?php

declare(strict_types=1);

namespace HiFolks\Fusion\Tests\Fixtures\Models;

use HiFolks\Fusion\Traits\FusionBaseModelTrait;
use Illuminate\Database\Eloquent\Model;

class ArticleWithoutFrontmatterRules extends Model
{
    use FusionBaseModelTrait;

    /**
     * @return array<int, string>
     */
    public function frontmatterFields(): array
    {
        return ['title', 'date', 'tags'];
    }
}
