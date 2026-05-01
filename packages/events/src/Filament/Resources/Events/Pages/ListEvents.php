<?php

declare(strict_types=1);

namespace Capell\Events\Filament\Resources\Events\Pages;

use Capell\Admin\Enums\ResourceEnum as AdminResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Resources\Pages\Pages\ListPages;
use Capell\Events\Enums\ResourceEnum;
use Capell\Events\Filament\Resources\Events\EventResource;
use Illuminate\Contracts\Support\Htmlable;

class ListEvents extends ListPages
{
    /** @return class-string<EventResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(AdminResourceEnum::Page, strtolower(ResourceEnum::Event->name));
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-events::generic.events_info');
    }
}
