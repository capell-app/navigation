<?php

declare(strict_types=1);

namespace Capell\Events\Support\Creator;

use Capell\Admin\Filament\Configurators\Pages\ResultsPageConfigurator;
use Capell\Admin\Filament\Configurators\Types\PageTypeConfigurator;
use Capell\Core\Actions\SetupPageUrlsAction;
use Capell\Core\Enums\LayoutGroupEnum;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Enums\TypeGroupEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Events\Enums\EventPageTypeEnum;
use Capell\Events\Enums\EventsLayoutEnum;
use Capell\Events\Enums\EventsTypeGroupEnum;
use Capell\Events\Enums\LivewirePageComponentEnum;
use Capell\Events\Filament\Configurators\Events\EventPageConfigurator;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class EventsCreator
{
    public function setup(Site $site): void
    {
        $eventsPage = $this->createEventsPage($site);
        $this->createCalendarPage($eventsPage);
        $this->createEventPageType();
    }

    public function createEventPageType(): Type
    {
        return Type::query()->firstOrCreate([
            'key' => EventPageTypeEnum::Event->value,
            'type' => TypeEnum::Page,
        ], [
            'name' => __('capell-events::generic.event'),
            'group' => EventsTypeGroupEnum::Event->value,
            'admin' => [
                'type_configurator' => PageTypeConfigurator::getKey(),
                'configurator' => EventPageConfigurator::getKey(),
                'icon' => 'heroicon-' . Heroicon::OutlinedCalendarDays->value,
                'required_fields' => ['title'],
            ],
            'meta' => [
                'with_date' => true,
                'with_image' => true,
                'with_summary' => true,
            ],
        ]);
    }

    public function createEventsPageType(): Type
    {
        return Type::query()->firstOrCreate([
            'key' => EventPageTypeEnum::Events->value,
            'type' => TypeEnum::Page,
        ], [
            'name' => __('capell-events::generic.events'),
            'group' => TypeGroupEnum::System->value,
            'admin' => [
                'type_configurator' => PageTypeConfigurator::getKey(),
                'configurator' => ResultsPageConfigurator::getKey(),
                'icon' => 'heroicon-' . Heroicon::OutlinedCalendarDays->value,
                'required_fields' => ['title'],
            ],
            'meta' => [
                'component' => LivewirePageComponentEnum::EventsPage,
                'livewire' => true,
                'limit' => 12,
                'pagination' => true,
            ],
        ]);
    }

    public function createCalendarPageType(): Type
    {
        return Type::query()->firstOrCreate([
            'key' => EventPageTypeEnum::Calendar->value,
            'type' => TypeEnum::Page,
        ], [
            'name' => __('capell-events::generic.calendar'),
            'group' => TypeGroupEnum::System->value,
            'admin' => [
                'type_configurator' => PageTypeConfigurator::getKey(),
                'configurator' => ResultsPageConfigurator::getKey(),
                'icon' => 'heroicon-' . Heroicon::OutlinedCalendarDays->value,
                'required_fields' => ['title'],
            ],
            'meta' => [
                'component' => LivewirePageComponentEnum::CalendarPage,
                'livewire' => true,
            ],
        ]);
    }

    public function createEventLayout(): Layout
    {
        return Layout::query()->firstOrCreate(['key' => EventsLayoutEnum::Event->value], [
            'name' => __('capell-events::generic.event'),
            'group' => LayoutGroupEnum::System->value,
            'containers' => [
                'main' => ['widgets' => [['widget_key' => 'breadcrumbs'], ['widget_key' => 'page-content']]],
            ],
        ]);
    }

    public function createEventsLayout(): Layout
    {
        return Layout::query()->firstOrCreate(['key' => EventsLayoutEnum::Events->value], [
            'name' => __('capell-events::generic.events'),
            'group' => LayoutGroupEnum::System->value,
            'containers' => [
                'main' => ['widgets' => [['widget_key' => 'breadcrumbs'], ['widget_key' => 'page-content'], ['widget_key' => 'page-slot']]],
            ],
        ]);
    }

    public function createCalendarLayout(): Layout
    {
        return Layout::query()->firstOrCreate(['key' => EventsLayoutEnum::Calendar->value], [
            'name' => __('capell-events::generic.calendar'),
            'group' => LayoutGroupEnum::System->value,
            'containers' => [
                'main' => ['widgets' => [['widget_key' => 'breadcrumbs'], ['widget_key' => 'page-slot']]],
            ],
        ]);
    }

    public function createEventsPage(Site $site, ?Type $type = null, ?Layout $layout = null, ?Collection $languages = null): Page
    {
        $type ??= $this->createEventsPageType();
        $layout ??= $this->createEventsLayout();
        $languages ??= $site->getAllLanguages();

        $page = Page::query()->firstOrCreate([
            'layout_id' => $layout->id,
            'site_id' => $site->id,
            'type_id' => $type->id,
            'parent_id' => null,
        ], [
            'name' => __('capell-events::generic.events'),
        ]);

        $languages->each(function (Language $language) use ($page): void {
            $page->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-events::generic.events'),
                'meta' => ['slug' => 'events'],
            ]);
        });

        SetupPageUrlsAction::run($page);

        return $page;
    }

    public function createCalendarPage(Page $parent, ?Type $type = null, ?Layout $layout = null, ?Collection $languages = null): Page
    {
        $site = $parent->site;
        $type ??= $this->createCalendarPageType();
        $layout ??= $this->createCalendarLayout();
        $languages ??= $site->getAllLanguages();

        $page = Page::query()->firstOrCreate([
            'layout_id' => $layout->id,
            'site_id' => $site->id,
            'type_id' => $type->id,
            'parent_id' => $parent->id,
        ], [
            'name' => __('capell-events::generic.calendar'),
        ]);

        $languages->each(function (Language $language) use ($page): void {
            $page->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-events::generic.calendar'),
                'meta' => ['slug' => 'calendar'],
            ]);
        });

        SetupPageUrlsAction::run($page);

        return $page;
    }
}
