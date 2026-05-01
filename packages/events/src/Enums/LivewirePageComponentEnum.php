<?php

declare(strict_types=1);

namespace Capell\Events\Enums;

use Capell\Core\Enums\Attribute\Component;
use Capell\Core\Enums\Attribute\EnumAttributeHelper;
use Capell\Core\Enums\Attribute\EnumAttributeInterface;
use Capell\Events\Livewire\Page\Calendar;
use Capell\Events\Livewire\Page\Events;

enum LivewirePageComponentEnum: string implements EnumAttributeInterface
{
    use EnumAttributeHelper;

    #[Component(Calendar::class)]
    case CalendarPage = 'capell-events::page.calendar';

    #[Component(Events::class)]
    case EventsPage = 'capell-events::page.events';

    public static function getComponents(): array
    {
        $attributes = self::getAllCaseAttributes(Component::class);

        return array_map(fn (?Component $attribute): ?string => $attribute?->class ?? null, $attributes);
    }

    public function getComponent(): ?string
    {
        return $this->getCaseAttribute(Component::class)?->class;
    }
}
