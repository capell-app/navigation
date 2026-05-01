<?php

declare(strict_types=1);

namespace Capell\Events\Actions;

use Capell\Events\Enums\EventLocationTypeEnum;
use Capell\Events\Models\Event;
use Capell\Events\Models\EventOccurrence;
use Lorisleiva\Actions\Concerns\AsObject;

class BuildEventSchemaAction
{
    use AsObject;

    /** @return array<string, mixed> */
    public function handle(Event $event, ?EventOccurrence $occurrence = null): array
    {
        $location = $occurrence?->location ?? $event->meta['location'] ?? [];
        $booking = $occurrence?->booking ?? $event->meta['booking'] ?? [];
        $schema = $event->meta['schema'] ?? [];

        $eventSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $event->name,
            'description' => $event->translation?->summary ?? null,
            'startDate' => $occurrence?->starts_at?->toIso8601String(),
            'endDate' => $occurrence?->ends_at?->toIso8601String(),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => $this->getAttendanceMode($location['type'] ?? EventLocationTypeEnum::Physical->value),
            'location' => $this->getLocationSchema($location),
            'url' => $event->pageUrl?->full_url,
        ];

        if (isset($booking['url']) && $booking['url'] !== '') {
            $eventSchema['offers'] = [
                '@type' => 'Offer',
                'url' => $booking['url'],
                'name' => $booking['label'] ?? __('capell-events::form.booking'),
            ];
        }

        if (isset($schema['organizer']) && $schema['organizer'] !== '') {
            $eventSchema['organizer'] = [
                '@type' => 'Organization',
                'name' => $schema['organizer'],
            ];
        }

        return array_filter($eventSchema, fn (mixed $value): bool => $value !== null && $value !== []);
    }

    private function getAttendanceMode(string $locationType): string
    {
        return match ($locationType) {
            EventLocationTypeEnum::Online->value => 'https://schema.org/OnlineEventAttendanceMode',
            EventLocationTypeEnum::Hybrid->value => 'https://schema.org/MixedEventAttendanceMode',
            default => 'https://schema.org/OfflineEventAttendanceMode',
        };
    }

    /** @param  array<string, mixed>  $location */
    private function getLocationSchema(array $location): array
    {
        if (($location['type'] ?? null) === EventLocationTypeEnum::Online->value) {
            return [
                '@type' => 'VirtualLocation',
                'url' => $location['url'] ?? null,
            ];
        }

        return array_filter([
            '@type' => 'Place',
            'name' => $location['name'] ?? null,
            'address' => $location['address'] ?? null,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
