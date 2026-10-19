<?php

declare(strict_types=1);

namespace HiFolks\Fusion\Tests\Fixtures\Models;

use HiFolks\Fusion\Traits\FusionBaseModelTrait;
use Illuminate\Database\Eloquent\Model;

class ArticleWithFrontmatterRules extends Model
{
    use FusionBaseModelTrait;

    /**
     * @return array<string, array<int, string>>
     */
    public function frontmatterRules(): array
    {
        return [
            'title' => ['required', 'string'],
            'date' => ['required', 'date'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string'],
        ];
    }
}
