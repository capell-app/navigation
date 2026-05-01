<?php

declare(strict_types=1);

namespace Capell\Events\Actions;

use Capell\Events\Data\EventScheduleData;
use Capell\Events\Enums\EventOccurrenceStatusEnum;
use Capell\Events\Enums\EventRecurrenceFrequencyEnum;
use Capell\Events\Models\Event;
use Capell\Events\Models\EventOccurrence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

class GenerateEventOccurrencesAction
{
    use AsObject;

    private const WEEKDAYS = [
        'monday' => 1,
        'tuesday' => 2,
        'wednesday' => 3,
        'thursday' => 4,
        'friday' => 5,
        'saturday' => 6,
        'sunday' => 0,
    ];

    /** @return Collection<int, EventOccurrence> */
    public function handle(Event $event, ?CarbonImmutable $minimumStartsAt = null): Collection
    {
        $schedule = $this->getSchedule($event);

        if (! $schedule instanceof EventScheduleData) {
            return collect();
        }

        $startsAt = $schedule->startsAt;
        $endsAt = $schedule->endsAt;
        $durationSeconds = $endsAt instanceof CarbonImmutable ? $endsAt->diffInSeconds($startsAt, true) : null;
        $dates = $this->buildDates($schedule)
            ->when(
                $minimumStartsAt instanceof CarbonImmutable,
                fn (Collection $dates): Collection => $dates
                    ->filter(fn (CarbonImmutable $occurrenceStartsAt): bool => $occurrenceStartsAt->greaterThanOrEqualTo($minimumStartsAt))
                    ->values(),
            );

        return $dates->map(fn (CarbonImmutable $occurrenceStartsAt): EventOccurrence => $event->occurrences()->updateOrCreate([
            'starts_at' => $occurrenceStartsAt,
        ], [
            'site_id' => $event->site_id,
            'ends_at' => $durationSeconds !== null ? $occurrenceStartsAt->addSeconds($durationSeconds) : null,
            'timezone' => $schedule->timezone,
            'status' => EventOccurrenceStatusEnum::Scheduled->value,
            'location' => $event->meta['location'] ?? [],
            'booking' => $event->meta['booking'] ?? [],
            'schema' => $event->meta['schema'] ?? [],
            'is_cancelled' => false,
        ]));
    }

    private function getSchedule(Event $event): ?EventScheduleData
    {
        $schedule = $event->meta['schedule'] ?? null;

        if (! is_array($schedule) || ! isset($schedule['starts_at'])) {
            return null;
        }

        return EventScheduleData::from($schedule);
    }

    /** @return Collection<int, CarbonImmutable> */
    private function buildDates(EventScheduleData $schedule): Collection
    {
        $frequency = $schedule->recurrence?->frequency ?? EventRecurrenceFrequencyEnum::None;

        return match ($frequency) {
            EventRecurrenceFrequencyEnum::Daily => $this->buildDailyDates($schedule),
            EventRecurrenceFrequencyEnum::Weekly => $this->buildWeeklyDates($schedule),
            EventRecurrenceFrequencyEnum::Monthly => $this->buildMonthlyDates($schedule),
            EventRecurrenceFrequencyEnum::None => collect([$schedule->startsAt]),
        };
    }

    /** @return Collection<int, CarbonImmutable> */
    private function buildDailyDates(EventScheduleData $schedule): Collection
    {
        $dates = collect();
        $currentDate = $schedule->startsAt;
        $interval = max(1, $schedule->recurrence?->interval ?? 1);
        $maximumDate = $this->getMaximumDate($schedule);
        $maximumCount = $schedule->recurrence?->count;

        while ($currentDate->lessThanOrEqualTo($maximumDate) && ($maximumCount === null || $dates->count() < $maximumCount)) {
            $dates->push($currentDate);
            $currentDate = $currentDate->addDays($interval);
        }

        return $dates;
    }

    /** @return Collection<int, CarbonImmutable> */
    private function buildWeeklyDates(EventScheduleData $schedule): Collection
    {
        $dates = collect();
        $currentDate = $schedule->startsAt;
        $maximumDate = $this->getMaximumDate($schedule);
        $maximumCount = $schedule->recurrence?->count;
        $weekdays = $this->getWeekdayNumbers($schedule);

        while ($currentDate->lessThanOrEqualTo($maximumDate) && ($maximumCount === null || $dates->count() < $maximumCount)) {
            if (in_array($currentDate->dayOfWeek, $weekdays, true)) {
                $dates->push($currentDate);
            }

            $currentDate = $currentDate->addDay();
        }

        return $dates;
    }

    /** @return Collection<int, CarbonImmutable> */
    private function buildMonthlyDates(EventScheduleData $schedule): Collection
    {
        $dates = collect();
        $currentDate = $schedule->startsAt;
        $interval = max(1, $schedule->recurrence?->interval ?? 1);
        $maximumDate = $this->getMaximumDate($schedule);
        $maximumCount = $schedule->recurrence?->count;

        while ($currentDate->lessThanOrEqualTo($maximumDate) && ($maximumCount === null || $dates->count() < $maximumCount)) {
            $dates->push($currentDate);
            $currentDate = $currentDate->addMonthsNoOverflow($interval);
        }

        return $dates;
    }

    private function getMaximumDate(EventScheduleData $schedule): CarbonImmutable
    {
        $candidates = collect([
            $schedule->generateUntil?->endOfDay(),
            $schedule->recurrence?->until ? CarbonImmutable::parse($schedule->recurrence->until)->endOfDay() : null,
            $schedule->startsAt->addMonths(config('capell-events.default_generation_months', 18)),
        ])->filter(fn (?CarbonImmutable $date): bool => $date instanceof CarbonImmutable);

        return $candidates->min();
    }

    /** @return array<int, int> */
    private function getWeekdayNumbers(EventScheduleData $schedule): array
    {
        $weekdays = $schedule->recurrence?->weekdays ?? [];

        if ($weekdays === []) {
            return [$schedule->startsAt->dayOfWeek];
        }

        return collect($weekdays)
            ->map(fn (string $weekday): ?int => self::WEEKDAYS[$weekday] ?? null)
            ->filter(fn (?int $weekday): bool => $weekday !== null)
            ->values()
            ->all();
    }
}
