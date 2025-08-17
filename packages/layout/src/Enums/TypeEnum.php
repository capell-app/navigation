<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

enum TypeEnum: string
{
    case Widget = 'widget';

    public function getModel(): string
    {
        return match ($this) {
            self::Widget => LayoutModelEnum::Widget->value
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Widget => __('capell-layout::generic.widget')
        };
    }
}
