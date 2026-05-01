<?php

declare(strict_types=1);

namespace Capell\Events\Actions;

use Capell\Core\Models\Site;
use Capell\Events\Data\EventCalendarDayData;
use Capell\Events\Models\EventOccurrence;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

class BuildEventCalendarMonthAction
{
    use AsObject;

    /** @return Collection<int, EventCalendarDayData> */
    public function handle(Site $site, CarbonImmutable $month, ?CarbonImmutable $selectedDate = null): Collection
    {
        $monthStart = $month->startOfMonth();
        $selectedDate = $selectedDate?->startOfDay();
        $calendarStart = $monthStart->startOfWeek(CarbonInterface::MONDAY);
        $calendarEnd = $monthStart->endOfMonth()->endOfWeek(CarbonInterface::SUNDAY);
        $calendarEnd = $calendarStart->diffInDays($calendarEnd) < 41
            ? $calendarStart->addDays(41)
            : $calendarEnd;

        $occurrenceCounts = EventOccurrence::query()
            ->where('site_id', $site->id)
            ->notCancelled()
            ->between($calendarStart->startOfDay(), $calendarEnd->endOfDay())
            ->get()
            ->countBy(fn (EventOccurrence $occurrence): string => $occurrence->starts_at->format('Y-m-d'));

        $days = collect();
        $currentDate = $calendarStart->startOfDay();

        while ($currentDate->lessThanOrEqualTo($calendarEnd->startOfDay())) {
            $days->push(new EventCalendarDayData(
                date: $currentDate,
                isCurrentMonth: $currentDate->month === $monthStart->month,
                isToday: $currentDate->isSameDay(CarbonImmutable::now()),
                isSelected: $selectedDate instanceof CarbonImmutable && $currentDate->isSameDay($selectedDate),
                occurrenceCount: $occurrenceCounts[$currentDate->format('Y-m-d')] ?? 0,
            ));

            $currentDate = $currentDate->addDay();
        }

        return $days;
    }
}
