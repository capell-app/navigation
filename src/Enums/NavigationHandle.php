<?php

declare(strict_types=1);

namespace Capell\Navigation\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationHandle: string implements HasLabel
{
    case Main = 'main';

    case Footer = 'footer';

    case SubFooter = 'sub-footer';

    /**
     * @return array<array-key, mixed>
     */
    public static function getValues(): array
    {
        return self::cases();
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Main => __('capell::generic.main'),
            self::Footer => __('capell::generic.footer'),
            self::SubFooter => __('capell::generic.sub_footer'),
        };
    }
}
