<?php

declare(strict_types=1);

namespace Capell\Events\Filament\Configurators\Events;

use Capell\Admin\Contracts\Schemas\PageSchemaExtenderResolverInterface;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Admin\Filament\Components\Forms\Page\LayoutSelect;
use Capell\Admin\Filament\Components\Forms\Page\SettingsSchema;
use Capell\Admin\Filament\Components\Forms\Page\SiteSelect;
use Capell\Admin\Filament\Components\Forms\Page\Tab\SettingsTab;
use Capell\Admin\Filament\Components\Forms\PublishSchema;
use Capell\Admin\Filament\Components\Forms\PublishSection;
use Capell\Admin\Filament\Configurators\Pages\DefaultPageConfigurator;
use Capell\Admin\Filament\Resources\Pages\RelationManagers\UrlsRelationManager;
use Capell\Events\Enums\EventLocationTypeEnum;
use Capell\Events\Enums\EventRecurrenceFrequencyEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Override;

class EventPageConfigurator extends DefaultPageConfigurator
{
    protected bool $hasCreatePageSchema = false;

    public static function relationManagers(Model $record): array
    {
        return [
            UrlsRelationManager::class,
        ];
    }

    #[Override]
    protected function getEditFormSchema(Schema $schema): array
    {
        return [
            FixedWidthSidebar::make()
                ->mainSchema([
                    $this->getTranslationFormSchema($schema),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->gridContainer()
                        ->columns(['@md' => 2])
                        ->schema([
                            ...SettingsSchema::make(
                                $schema,
                                components: [
                                    MediaLibraryFileUpload::make('image'),
                                ],
                                pageGroup: $schema->getLivewire()->getResource()::getResourceName(),
                                withParent: false,
                                withType: false,
                            ),
                            PublishSection::make()->collapsed(),
                        ]),
                ]),
            Tabs::make()
                ->columnSpanFull()
                ->tabs($this->getTabs($schema)),
        ];
    }

    #[Override]
    protected function getEditOptionFormSchema(Schema $schema): array
    {
        return [
            $this->getTranslationFormSchema($schema),
            Section::make(__('capell-admin::generic.settings'))
                ->compact()
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->schema([
                    ...SettingsSchema::make(
                        $schema,
                        components: [
                            MediaLibraryFileUpload::make('image'),
                        ],
                        pageGroup: $schema->getLivewire()->getResource()::getResourceName(),
                        withParent: false,
                        withType: false,
                    ),
                    PublishSection::make(),
                ]),
            ...self::getEventSections(),
        ];
    }

    #[Override]
    protected function getCreateExtraFor(Schema $schema): array
    {
        return [
            SiteSelect::make(),
            LayoutSelect::make('layout_id')
                ->reactive(),
            PublishSchema::make($schema),
            ...self::getEventSections(),
        ];
    }

    protected function getTabs(Schema $schema): array
    {
        return resolve(PageSchemaExtenderResolverInterface::class)->resolveTabs($schema, [
            self::getScheduleTab(),
            self::getLocationTab(),
            self::getBookingTab(),
            self::getSchemaTab(),
            SettingsTab::make($schema),
        ]);
    }

    /**
     * @return array<int, Section>
     */
    private static function getEventSections(): array
    {
        return [
            self::getScheduleSection(),
            self::getLocationSection(),
            self::getBookingSection(),
            self::getSchemaSection(),
        ];
    }

    private static function getScheduleTab(): Tab
    {
        return Tab::make(__('capell-events::form.schedule'))
            ->icon(Heroicon::OutlinedCalendarDays)
            ->schema([
                self::getScheduleSection(),
            ]);
    }

    private static function getLocationTab(): Tab
    {
        return Tab::make(__('capell-events::form.location'))
            ->icon(Heroicon::OutlinedMapPin)
            ->schema([
                self::getLocationSection(),
            ]);
    }

    private static function getBookingTab(): Tab
    {
        return Tab::make(__('capell-events::form.booking'))
            ->icon(Heroicon::OutlinedTicket)
            ->schema([
                self::getBookingSection(),
            ]);
    }

    private static function getSchemaTab(): Tab
    {
        return Tab::make(__('capell-events::form.schema'))
            ->icon(Heroicon::OutlinedCodeBracketSquare)
            ->schema([
                self::getSchemaSection(),
            ]);
    }

    private static function getScheduleSection(): Section
    {
        return Section::make(__('capell-events::form.schedule'))
            ->compact()
            ->columns(3)
            ->statePath('meta.schedule')
            ->schema([
                DateTimePicker::make('starts_at')
                    ->label(__('capell-events::form.starts_at'))
                    ->seconds(false)
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->label(__('capell-events::form.ends_at'))
                    ->seconds(false)
                    ->after('starts_at'),
                TextInput::make('timezone')
                    ->label(__('capell-events::form.timezone'))
                    ->default(config('app.timezone', 'UTC'))
                    ->required(),
                Select::make('recurrence.frequency')
                    ->label(__('capell-events::form.recurrence'))
                    ->options(EventRecurrenceFrequencyEnum::class)
                    ->default(EventRecurrenceFrequencyEnum::None->value)
                    ->required(),
                TextInput::make('recurrence.interval')
                    ->label(__('capell-events::form.recurrence_interval'))
                    ->numeric()
                    ->minValue(1)
                    ->default(1),
                CheckboxList::make('recurrence.weekdays')
                    ->label(__('capell-events::form.weekdays'))
                    ->options(self::getWeekdayOptions())
                    ->columns(4),
                DatePicker::make('recurrence.until')
                    ->label(__('capell-events::form.recurrence_until')),
                TextInput::make('recurrence.count')
                    ->label(__('capell-events::form.recurrence_count'))
                    ->numeric()
                    ->minValue(1),
                DatePicker::make('generate_until')
                    ->label(__('capell-events::form.generate_until')),
            ]);
    }

    private static function getLocationSection(): Section
    {
        return Section::make(__('capell-events::form.location'))
            ->compact()
            ->columns(3)
            ->statePath('meta.location')
            ->schema([
                Select::make('type')
                    ->label(__('capell-events::form.location_type'))
                    ->options(EventLocationTypeEnum::class)
                    ->default(EventLocationTypeEnum::Physical->value)
                    ->required(),
                TextInput::make('name')
                    ->label(__('capell-events::form.location_name'))
                    ->maxLength(255),
                TextInput::make('url')
                    ->label(__('capell-events::form.location_url'))
                    ->url()
                    ->maxLength(255),
                TextInput::make('address')
                    ->label(__('capell-events::form.location_address'))
                    ->columnSpanFull()
                    ->maxLength(500),
                TextInput::make('latitude')
                    ->label(__('capell-events::form.latitude'))
                    ->numeric(),
                TextInput::make('longitude')
                    ->label(__('capell-events::form.longitude'))
                    ->numeric(),
            ]);
    }

    private static function getBookingSection(): Section
    {
        return Section::make(__('capell-events::form.booking'))
            ->compact()
            ->columns(2)
            ->statePath('meta.booking')
            ->schema([
                TextInput::make('url')
                    ->label(__('capell-events::form.booking_url'))
                    ->url()
                    ->maxLength(255),
                TextInput::make('label')
                    ->label(__('capell-events::form.booking_label'))
                    ->maxLength(80),
                DateTimePicker::make('opens_at')
                    ->label(__('capell-events::form.booking_opens_at'))
                    ->seconds(false),
                DateTimePicker::make('closes_at')
                    ->label(__('capell-events::form.booking_closes_at'))
                    ->seconds(false)
                    ->after('opens_at'),
            ]);
    }

    private static function getSchemaSection(): Section
    {
        return Section::make(__('capell-events::form.schema'))
            ->compact()
            ->columns(2)
            ->statePath('meta.schema')
            ->schema([
                TextInput::make('organizer')
                    ->label(__('capell-events::form.organizer'))
                    ->maxLength(255),
                TextInput::make('performer')
                    ->label(__('capell-events::form.performer'))
                    ->maxLength(255),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function getWeekdayOptions(): array
    {
        return [
            'monday' => __('capell-events::form.weekday.monday'),
            'tuesday' => __('capell-events::form.weekday.tuesday'),
            'wednesday' => __('capell-events::form.weekday.wednesday'),
            'thursday' => __('capell-events::form.weekday.thursday'),
            'friday' => __('capell-events::form.weekday.friday'),
            'saturday' => __('capell-events::form.weekday.saturday'),
            'sunday' => __('capell-events::form.weekday.sunday'),
        ];
    }
}
