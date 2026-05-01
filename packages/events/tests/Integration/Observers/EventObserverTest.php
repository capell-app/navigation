<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Events\Models\Event;
use Carbon\CarbonImmutable;

it('syncs occurrences when a scheduled event is saved', function (): void {
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
            'location' => ['type' => 'physical', 'name' => 'Town Hall'],
        ],
    ]);

    expect($event->occurrences()->count())->toBe(1)
        ->and($event->occurrences()->first()?->starts_at->format('Y-m-d H:i:s'))->toBe('2026-06-01 09:00:00');

    CarbonImmutable::setTestNow();
});
