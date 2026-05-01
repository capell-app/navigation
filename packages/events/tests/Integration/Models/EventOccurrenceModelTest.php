<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Events\Models\Event;
use Capell\Events\Models\EventOccurrence;

it('filters upcoming non-cancelled occurrences by date range', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $event = Event::factory()->site($site)->create();

    EventOccurrence::factory()->event($event)->site($site)->create([
        'starts_at' => '2026-06-01 09:00:00',
        'is_cancelled' => false,
    ]);

    EventOccurrence::factory()->event($event)->site($site)->create([
        'starts_at' => '2026-06-02 09:00:00',
        'is_cancelled' => true,
    ]);

    $occurrences = EventOccurrence::query()
        ->between('2026-06-01 00:00:00', '2026-06-01 23:59:59')
        ->notCancelled()
        ->get();

    expect($occurrences)->toHaveCount(1)
        ->and($occurrences->first()?->starts_at->format('Y-m-d H:i:s'))->toBe('2026-06-01 09:00:00');
});
