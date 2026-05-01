<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Events\Actions\BuildEventSchemaAction;
use Capell\Events\Models\Event;
use Capell\Events\Models\EventOccurrence;

it('builds event schema from event and occurrence data', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $event = Event::factory()->site($site)->create([
        'name' => 'Spring meetup',
        'meta' => [
            'location' => ['type' => 'physical', 'name' => 'Town Hall', 'address' => 'Birmingham'],
            'booking' => ['url' => 'https://example.test/book', 'label' => 'Book tickets'],
            'schema' => ['organizer' => 'Capell'],
        ],
    ]);
    $occurrence = EventOccurrence::factory()->event($event)->site($site)->create([
        'starts_at' => '2026-06-01 09:00:00',
        'ends_at' => '2026-06-01 10:00:00',
        'location' => ['type' => 'physical', 'name' => 'Town Hall', 'address' => 'Birmingham'],
        'booking' => ['url' => 'https://example.test/book', 'label' => 'Book tickets'],
    ]);

    $schema = BuildEventSchemaAction::run($event, $occurrence);

    expect($schema)
        ->toHaveKey('@context', 'https://schema.org')
        ->toHaveKey('@type', 'Event')
        ->toHaveKey('name', 'Spring meetup')
        ->toHaveKey('startDate')
        ->toHaveKey('endDate')
        ->toHaveKey('eventAttendanceMode')
        ->toHaveKey('location')
        ->toHaveKey('offers');
});
