<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Events\Models\Event;
use Capell\Events\Models\EventOccurrence;
use Capell\Events\Support\RenderHooks\RegisterEventSchemaHook;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Enums\RenderHookScenario;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Carbon\CarbonImmutable;

it('renders event schema in the frontend head hook', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01 00:00:00'));

    $site = Site::factory()->withTranslations()->create();
    $event = Event::factory()->site($site)->create([
        'name' => 'Spring meetup',
        'meta' => [
            'location' => ['type' => 'physical', 'name' => 'Town Hall'],
            'booking' => ['url' => 'https://example.test/book', 'label' => 'Book'],
            'schema' => ['organizer' => 'Capell'],
        ],
    ]);

    EventOccurrence::factory()->event($event)->site($site)->create([
        'starts_at' => '2026-06-01 09:00:00',
        'ends_at' => '2026-06-01 10:00:00',
        'location' => ['type' => 'physical', 'name' => 'Town Hall'],
        'booking' => ['url' => 'https://example.test/book', 'label' => 'Book'],
    ]);

    expect($event->fresh()->nextOccurrence)->toBeInstanceOf(EventOccurrence::class);

    $registry = app(RenderHookRegistry::class);
    (new RegisterEventSchemaHook($registry))->register();

    expect($registry->get(RenderHookLocation::HeadClose))->not->toBeEmpty();

    $output = $registry->renderAll(
        RenderHookLocation::HeadClose,
        item: ['page' => $event, 'site' => $site, 'language' => $site->language],
        scenario: RenderHookScenario::SeoMeta->value,
    );

    expect($output)
        ->toContain('application/ld+json')
        ->toContain('Spring meetup')
        ->toContain('https://schema.org');

    CarbonImmutable::setTestNow();
});
