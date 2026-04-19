<?php

declare(strict_types=1);

namespace Capell\Themes\Admin\Pages;

use BackedEnum;
use Capell\Themes\Admin\Schemas\ThemeSettingsSchema;
use Filament\Pages\Page;
use UnitEnum;

class ThemeSettingsPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-paint-brush';

    protected string $view = 'themes-admin::pages.theme-settings';

    protected static ?string $navigationLabel = 'Theme Settings';

    protected static UnitEnum|string|null $navigationGroup = 'Appearance';

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public function getTitle(): string
    {
        return 'Theme Settings';
    }

    protected function getFormSchema(): array
    {
        return [ThemeSettingsSchema::make()];
    }
}
