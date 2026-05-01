<?php

declare(strict_types=1);

use Capell\Events\Data\EventBookingData;
use Capell\Events\Data\EventLocationData;
use Capell\Events\Data\EventScheduleData;
use Capell\Events\Enums\EventLocationTypeEnum;
use Capell\Events\Enums\EventRecurrenceFrequencyEnum;
use Carbon\CarbonImmutable;

it('creates schedule data with recurrence', function (): void {
    $schedule = EventScheduleData::from([
        'starts_at' => '2026-06-01 09:00:00',
        'ends_at' => '2026-06-01 10:30:00',
        'timezone' => 'Europe/London',
        'recurrence' => [
            'frequency' => 'weekly',
            'interval' => 1,
            'weekdays' => ['monday'],
            'month_day' => null,
            'until' => '2026-07-01',
            'count' => null,
        ],
        'generate_until' => '2026-07-01',
    ]);

    expect($schedule->startsAt)->toBeInstanceOf(CarbonImmutable::class)
        ->and($schedule->recurrence->frequency)->toBe(EventRecurrenceFrequencyEnum::Weekly)
        ->and($schedule->recurrence->weekdays)->toBe(['monday']);
});

it('creates physical location and booking data', function (): void {
    $location = EventLocationData::from([
        'type' => 'physical',
        'name' => 'Town Hall',
        'address' => 'Victoria Square, Birmingham',
        'url' => null,
        'latitude' => 52.479,
        'longitude' => -1.902,
    ]);

    $booking = EventBookingData::from([
        'url' => 'https://example.test/book',
        'label' => 'Book tickets',
        'opens_at' => null,
        'closes_at' => null,
    ]);

    expect($location->type)->toBe(EventLocationTypeEnum::Physical)
        ->and($booking->url)->toBe('https://example.test/book');
});
