<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Events\Actions\GenerateEventOccurrencesAction;
use Capell\Events\Models\Event;

it('generates one occurrence for a one-off event', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $event = Event::factory()->site($site)->create([
        'meta' => [
            'schedule' => [
                'starts_at' => '2026-06-01 09:00:00',
                'ends_at' => '2026-06-01 10:00:00',
                'timezone' => 'Europe/London',
                'recurrence' => ['frequency' => 'none', 'interval' => 1, 'weekdays' => [], 'month_day' => null, 'until' => null, 'count' => null],
                'generate_until' => null,
            ],
            'location' => ['type' => 'physical', 'name' => 'Town Hall'],
            'booking' => ['url' => 'https://example.test/book', 'label' => 'Book'],
        ],
    ]);

    GenerateEventOccurrencesAction::run($event);

    expect($event->occurrences)->toHaveCount(1)
        ->and($event->occurrences->first()?->starts_at->format('Y-m-d H:i:s'))->toBe('2026-06-01 09:00:00')
        ->and($event->occurrences->first()?->location)->toBe(['type' => 'physical', 'name' => 'Town Hall']);
});

it('generates weekly occurrences on selected weekdays', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $event = Event::factory()->site($site)->create([
        'meta' => [
            'schedule' => [
                'starts_at' => '2026-06-01 09:00:00',
                'ends_at' => '2026-06-01 10:00:00',
                'timezone' => 'Europe/London',
                'generate_until' => '2026-06-15',
                'recurrence' => [
                    'frequency' => 'weekly',
                    'interval' => 1,
                    'weekdays' => ['monday', 'wednesday'],
                    'month_day' => null,
                    'until' => '2026-06-15',
                    'count' => null,
                ],
            ],
            'location' => ['type' => 'physical', 'name' => 'Town Hall'],
            'booking' => ['url' => 'https://example.test/book', 'label' => 'Book'],
        ],
    ]);

    GenerateEventOccurrencesAction::run($event);

    expect($event->occurrences()->orderBy('starts_at')->pluck('starts_at')->map->format('Y-m-d H:i:s')->all())
        ->toBe([
            '2026-06-01 09:00:00',
            '2026-06-03 09:00:00',
            '2026-06-08 09:00:00',
            '2026-06-10 09:00:00',
            '2026-06-15 09:00:00',
        ]);
});
