<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Events\Actions\BuildIcsFeedAction;
use Capell\Events\Models\Event;
use Capell\Events\Models\EventOccurrence;
use Carbon\CarbonImmutable;

it('builds an iCalendar feed for upcoming occurrences', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01 00:00:00'));

    $site = Site::factory()->withTranslations()->create();
    $event = Event::factory()->site($site)->create(['name' => 'Spring meetup']);
    EventOccurrence::factory()->event($event)->site($site)->create([
        'starts_at' => '2026-06-01 09:00:00',
        'ends_at' => '2026-06-01 10:00:00',
        'location' => ['name' => 'Town Hall'],
    ]);

    $feed = BuildIcsFeedAction::run($site);

    expect($feed)->toContain('BEGIN:VCALENDAR')
        ->toContain('VERSION:2.0')
        ->toContain('BEGIN:VEVENT')
        ->toContain('UID:event-')
        ->toContain('DTSTART:')
        ->toContain('DTEND:')
        ->toContain('SUMMARY:Spring meetup')
        ->toContain('END:VEVENT')
        ->toContain('END:VCALENDAR');

    CarbonImmutable::setTestNow();
});
