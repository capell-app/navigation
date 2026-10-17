<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Components\Forms\Navigation;

use Capell\Admin\Filament\Components\Forms\BlueprintSelect as BaseBlueprintSelect;
use Override;

class TypeSelect extends BaseBlueprintSelect
{
    #[Override]
    protected function setUp(?string $label = null): void
    {
        parent::setUp($label);

        $this->subject('navigation');
    }
}
