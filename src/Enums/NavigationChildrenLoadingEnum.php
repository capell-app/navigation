<?php

declare(strict_types=1);

namespace Capell\Navigation\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationChildrenLoadingEnum: string implements HasLabel
{
    case Eager = 'eager';
    case Lazy = 'lazy';

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromItemData(array $data): self
    {
        $childrenLoading = $data['children_loading'] ?? null;

        if (is_string($childrenLoading)) {
            return self::tryFrom($childrenLoading) ?? self::Eager;
        }

        return ($data['lazy_children'] ?? false) === true ? self::Lazy : self::Eager;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Eager => __('capell-navigation::generic.children_loading_eager'),
            self::Lazy => __('capell-navigation::generic.children_loading_lazy'),
        };
    }
}
