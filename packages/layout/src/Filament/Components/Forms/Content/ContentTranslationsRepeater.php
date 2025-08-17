<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\ContentEditor;
use Capell\Admin\Filament\Components\Forms\RepeaterTabs;
use Capell\Admin\Filament\Components\Forms\TranslationLanguageSelect;
use Capell\Admin\Filament\Components\Forms\TranslationsRepeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

final class ContentTranslationsRepeater
{
    public static function make(
        Schema $schema,
        array $components = [],
        bool $hasTitle = true,
        bool $hasContent = true,
        bool $titleRequired = true
    ): RepeaterTabs {
        $operation = $schema->getOperation();

        return TranslationsRepeater::make('translations')
            ->when(
                $operation === 'replicate',
                fn (TranslationsRepeater $repeater): TranslationsRepeater => $repeater->withoutRelationship()
            )
            ->schema([
                ...($hasTitle ? self::getTitleSchema($titleRequired) : []),
                ...($hasContent ? self::getContentSchema() : []),
                ...$components,
            ]);
    }

    private static function getContentSchema(): array
    {
        return [
            ContentEditor::make(),
        ];
    }

    private static function getTitleSchema(bool $titleRequired): array
    {
        return [
            Grid::make(3)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('title')
                        ->label(__('capell-admin::form.title'))
                        ->required($titleRequired)
                        ->columnSpan(fn (Get $get): int => $get('language_id') ? 3 : 2),

                    TranslationLanguageSelect::make()
                        ->dehydratedWhenHidden()
                        ->hidden(fn (?int $state): bool => (bool) $state),
                ]),
        ];
    }
}
