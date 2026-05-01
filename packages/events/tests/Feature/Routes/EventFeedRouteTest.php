<?php

declare(strict_types=1);

use Capell\Core\Models\SiteDomain;
use Capell\Events\Models\Event;
use Capell\Events\Models\EventOccurrence;
use Carbon\CarbonImmutable;

use function Pest\Laravel\get;

it('serves the calendar feed for the request site domain', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01 00:00:00'));

    $defaultDomain = SiteDomain::factory()->default()->create();
    $requestDomain = SiteDomain::factory()->create([
        'domain' => 'events-two.test',
        'scheme' => 'http',
        'path' => null,
        'default' => true,
    ]);

    $defaultEvent = Event::factory()->site($defaultDomain->site)->create(['name' => 'Default site event']);
    EventOccurrence::factory()->event($defaultEvent)->site($defaultDomain->site)->create([
        'starts_at' => '2026-06-01 09:00:00',
    ]);

    $requestEvent = Event::factory()->site($requestDomain->site)->create(['name' => 'Requested site event']);
    EventOccurrence::factory()->event($requestEvent)->site($requestDomain->site)->create([
        'starts_at' => '2026-06-02 09:00:00',
    ]);

    get('http://events-two.test/events/feed.ics')
        ->assertOk()
        ->assertSee('Requested site event')
        ->assertDontSee('Default site event');

    CarbonImmutable::setTestNow();
});
