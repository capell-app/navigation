<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Events\Actions\BuildEventCalendarMonthAction;
use Capell\Events\Models\Event;
use Capell\Events\Models\EventOccurrence;
use Carbon\CarbonImmutable;

it('builds a complete calendar month with occurrence counts', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 12:00:00'));

    $site = Site::factory()->withTranslations()->create();
    $event = Event::factory()->site($site)->create();

    EventOccurrence::factory()->event($event)->site($site)->create([
        'starts_at' => '2026-06-08 09:00:00',
        'is_cancelled' => false,
    ]);

    $days = BuildEventCalendarMonthAction::run(
        $site,
        CarbonImmutable::parse('2026-06-01'),
        CarbonImmutable::parse('2026-06-08'),
    );

    expect($days)->toHaveCount(42)
        ->and($days->firstWhere('date', CarbonImmutable::parse('2026-06-08'))->occurrenceCount)->toBe(1)
        ->and($days->firstWhere('date', CarbonImmutable::parse('2026-06-08'))->isSelected)->toBeTrue();

    CarbonImmutable::setTestNow();
});
