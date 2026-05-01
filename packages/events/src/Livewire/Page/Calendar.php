<?php

declare(strict_types=1);

namespace Capell\Events\Livewire\Page;

use Capell\Events\Actions\BuildEventCalendarMonthAction;
use Capell\Events\Data\EventCalendarDayData;
use Capell\Events\Models\EventOccurrence;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Livewire\Page\AbstractPage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class Calendar extends AbstractPage
{
    public int $month;

    public ?string $selectedDate = null;

    public int $year;

    /** @var Collection<int, EventCalendarDayData> */
    public Collection $days;

    /** @var Collection<int, EventOccurrence> */
    public Collection $selectedOccurrences;

    protected static string $defaultView = 'capell-events::livewire.page.calendar';

    protected function setup(): void
    {
        $today = CarbonImmutable::now();
        $this->year ??= $today->year;
        $this->month ??= $today->month;
        $this->selectedDate ??= $today->format('Y-m-d');

        $this->refreshCalendar();
    }

    public function previousMonth(): void
    {
        $date = $this->monthDate()->subMonthNoOverflow();
        $this->year = $date->year;
        $this->month = $date->month;

        $this->refreshCalendar();
    }

    public function nextMonth(): void
    {
        $date = $this->monthDate()->addMonthNoOverflow();
        $this->year = $date->year;
        $this->month = $date->month;

        $this->refreshCalendar();
    }

    public function today(): void
    {
        $today = CarbonImmutable::now();
        $this->year = $today->year;
        $this->month = $today->month;
        $this->selectedDate = $today->format('Y-m-d');

        $this->refreshCalendar();
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = CarbonImmutable::parse($date)->format('Y-m-d');

        $this->refreshCalendar();
    }

    private function refreshCalendar(): void
    {
        $selectedDate = $this->selectedDate ? CarbonImmutable::parse($this->selectedDate) : null;
        $this->days = BuildEventCalendarMonthAction::run(Frontend::site(), $this->monthDate(), $selectedDate);

        $this->selectedOccurrences = $selectedDate instanceof CarbonImmutable
            ? EventOccurrence::query()
                ->with(['event.translation', 'event.pageUrl'])
                ->where('site_id', Frontend::site()->id)
                ->published()
                ->notCancelled()
                ->between($selectedDate->startOfDay(), $selectedDate->endOfDay())
                ->orderBy('starts_at')
                ->get()
            : collect();
    }

    private function monthDate(): CarbonImmutable
    {
        return CarbonImmutable::create($this->year, $this->month, 1)->startOfMonth();
    }
}
