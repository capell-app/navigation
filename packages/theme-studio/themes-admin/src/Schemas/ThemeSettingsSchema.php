<?php

declare(strict_types=1);

namespace Capell\Themes\Admin\Schemas;

use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\Themes\Admin\Rules\SafeCssColor;
use Capell\Themes\Core\Theme\ThemeRegistrar;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

final class ThemeSettingsSchema implements HasSchema
{
    public static function make(Schema $schema): array
    {
        return [self::tabs()];
    }

    public static function tabs(): Tabs
    {
        return Tabs::make(__('capell-themes-admin::settings.theme_settings'))
            ->tabs([
                Tab::make(__('capell-themes-admin::settings.tabs.theme'))
                    ->schema([
                        Select::make('active_theme')
                            ->label(__('capell-themes-admin::settings.active_theme'))
                            ->options(ThemeRegistrar::options())
                            ->required(),
                    ]),
                Tab::make(__('capell-themes-admin::settings.tabs.colors'))
                    ->schema([
                        ColorPicker::make('primary_color')
                            ->label(__('capell-themes-admin::settings.primary_color'))
                            ->required()
                            ->rule(new SafeCssColor),
                        ColorPicker::make('accent_color')
                            ->label(__('capell-themes-admin::settings.accent_color'))
                            ->required()
                            ->rule(new SafeCssColor),
                    ]),
                Tab::make(__('capell-themes-admin::settings.tabs.typography'))
                    ->schema([
                        Select::make('headline_font')
                            ->label(__('capell-themes-admin::settings.headline_font'))
                            ->options([
                                'playfair' => __('capell-themes-admin::settings.fonts.playfair'),
                                'sora' => __('capell-themes-admin::settings.fonts.sora'),
                                'inter' => __('capell-themes-admin::settings.fonts.inter'),
                            ])
                            ->required(),
                        Select::make('body_font')
                            ->label(__('capell-themes-admin::settings.body_font'))
                            ->options([
                                'inter' => __('capell-themes-admin::settings.fonts.inter'),
                                'manrope' => __('capell-themes-admin::settings.fonts.manrope'),
                            ])
                            ->required(),
                    ]),
                Tab::make(__('capell-themes-admin::settings.tabs.layout'))
                    ->schema([
                        Select::make('hero_style')
                            ->label(__('capell-themes-admin::settings.hero_style'))
                            ->options([
                                'image' => __('capell-themes-admin::settings.hero_styles.image'),
                                'gradient' => __('capell-themes-admin::settings.hero_styles.gradient'),
                                'video' => __('capell-themes-admin::settings.hero_styles.video'),
                            ])
                            ->required(),
                        Select::make('footer_layout')
                            ->label(__('capell-themes-admin::settings.footer_layout'))
                            ->options([
                                'minimal' => __('capell-themes-admin::settings.footer_layouts.minimal'),
                                'expanded' => __('capell-themes-admin::settings.footer_layouts.expanded'),
                                'newsletter' => __('capell-themes-admin::settings.footer_layouts.newsletter'),
                            ])
                            ->required(),
                        Select::make('spacing_preset')
                            ->label(__('capell-themes-admin::settings.spacing_preset'))
                            ->options([
                                'compact' => __('capell-themes-admin::settings.spacing_presets.compact'),
                                'balanced' => __('capell-themes-admin::settings.spacing_presets.balanced'),
                                'spacious' => __('capell-themes-admin::settings.spacing_presets.spacious'),
                            ])
                            ->required(),
                    ]),
                Tab::make(__('capell-themes-admin::settings.tabs.sections'))
                    ->schema([
                        Toggle::make('show_testimonials')
                            ->label(__('capell-themes-admin::settings.show_testimonials')),
                        Toggle::make('show_pricing')
                            ->label(__('capell-themes-admin::settings.show_pricing')),
                        Toggle::make('show_blog')
                            ->label(__('capell-themes-admin::settings.show_blog')),
                        Toggle::make('show_contact')
                            ->label(__('capell-themes-admin::settings.show_contact')),
                    ]),
            ]);
    }
}
