<?php

declare(strict_types=1);

namespace Capell\Events\Filament\Resources\Events;

use BackedEnum;
use Capell\Admin\Enums\ConfiguratorTypeEnum;
use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Events\Enums\EventsTypeGroupEnum;
use Capell\Events\Enums\ResourceEnum;
use Capell\Events\Filament\Resources\Events\Pages\CreateEvent;
use Capell\Events\Filament\Resources\Events\Pages\EditEvent;
use Capell\Events\Filament\Resources\Events\Pages\ListEvents;
use Capell\Events\Filament\Resources\Events\Schemas\EventForm;
use Capell\Events\Filament\Resources\Events\Tables\EventPagesTable;
use Capell\Events\Models\Event;
use Capell\Events\Providers\EventsServiceProvider;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Contracts\Support\Htmlable;

class EventResource extends PageResource
{
    protected static string $adminResourceName = ResourceEnum::Event->name;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'event';

    protected static string $tableConfigurator = EventPagesTable::class;

    protected static string $formConfigurator = EventForm::class;

    /** @return class-string<Event> */
    public static function getModel(): string
    {
        return Event::class;
    }

    public static function getResourceType(): ConfiguratorTypeEnum
    {
        return ConfiguratorTypeEnum::Page;
    }

    public static function getBasePath(Site $site, Language $language): string
    {
        return '/events/';
    }

    public static function getLabel(): string
    {
        return __('capell-events::generic.event');
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return Heroicon::OutlinedCalendarDays;
    }

    public static function getActiveNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return Heroicon::CalendarDays;
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-events::generic.events');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(EventsServiceProvider::$packageName)->isInstalled();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('capell-events::generic.events');
    }

    public static function applyTypeAdminResourceConstraint(BuilderContract $query, ?bool $hideSystemPages = false): void
    {
        $query->where('group', EventsTypeGroupEnum::Event);
    }
}
