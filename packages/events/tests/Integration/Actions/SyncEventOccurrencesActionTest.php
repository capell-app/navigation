<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Events\Actions\SyncEventOccurrencesAction;
use Capell\Events\Models\Event;
use Capell\Events\Models\EventOccurrence;
use Carbon\CarbonImmutable;

it('removes future stale occurrences before regenerating', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01 00:00:00'));

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
        ],
    ]);

    EventOccurrence::factory()->event($event)->site($site)->create(['starts_at' => '2026-04-01 09:00:00']);
    EventOccurrence::factory()->event($event)->site($site)->create(['starts_at' => '2026-07-01 09:00:00']);

    SyncEventOccurrencesAction::run($event);

    expect($event->occurrences()->orderBy('starts_at')->pluck('starts_at')->map->format('Y-m-d H:i:s')->all())
        ->toBe([
            '2026-04-01 09:00:00',
            '2026-06-01 09:00:00',
        ]);

    CarbonImmutable::setTestNow();
});

it('does not duplicate preserved past occurrences when syncing repeatedly', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 00:00:00'));

    $site = Site::factory()->withTranslations()->create();
    $event = Event::withoutEvents(fn (): Event => Event::factory()->site($site)->create([
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
        ],
    ]));

    EventOccurrence::factory()->event($event)->site($site)->create(['starts_at' => '2026-06-01 09:00:00']);

    SyncEventOccurrencesAction::run($event);
    SyncEventOccurrencesAction::run($event);

    expect($event->occurrences()->orderBy('starts_at')->pluck('starts_at')->map->format('Y-m-d H:i:s')->all())
        ->toBe([
            '2026-06-01 09:00:00',
            '2026-06-10 09:00:00',
            '2026-06-15 09:00:00',
        ]);

    CarbonImmutable::setTestNow();
});
