<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Mobile;

class TouchTargets
{
    public function __construct(private readonly int $size = 44) {}

    public function classes(): string
    {
        return 'min-h-[' . $this->size . 'px] min-w-[' . $this->size . 'px]';
    }

    public function inlineStyles(): string
    {
        return 'min-height:' . $this->size . 'px;min-width:' . $this->size . 'px;';
    }

    public function minSize(): int
    {
        return $this->size;
    }

    public function customClasses(int $size): string
    {
        return 'min-h-[' . $size . 'px] min-w-[' . $size . 'px]';
    }

    public function asAttributes(): string
    {
        return 'style="' . $this->inlineStyles() . '"';
    }
}
