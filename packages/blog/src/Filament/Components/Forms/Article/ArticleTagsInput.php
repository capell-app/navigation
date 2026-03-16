<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Components\Forms\Article;

use Capell\Blog\Filament\Components\Forms\TagsInput;
use Capell\Core\Enums\TypeEnum;

class ArticleTagsInput extends TagsInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->type(TypeEnum::Page->value);
    }
}
