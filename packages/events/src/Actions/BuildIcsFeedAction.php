<?php

declare(strict_types=1);

namespace Capell\Events\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Events\Models\EventOccurrence;
use Carbon\CarbonImmutable;
use Lorisleiva\Actions\Concerns\AsObject;

class BuildIcsFeedAction
{
    use AsObject;

    public function handle(Site $site, ?Language $language = null): string
    {
        $feedUntil = CarbonImmutable::now()->addMonths(config('capell-events.feed_months', 12));

        $events = EventOccurrence::query()
            ->with(['event.pageUrl'])
            ->where('site_id', $site->id)
            ->published()
            ->notCancelled()
            ->between(CarbonImmutable::now(), $feedUntil)
            ->orderBy('starts_at')
            ->get()
            ->map(fn (EventOccurrence $occurrence): string => $this->buildEvent($occurrence))
            ->implode("\r\n");

        return implode("\r\n", array_filter([
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Capell//Events//EN',
            'CALSCALE:GREGORIAN',
            $events,
            'END:VCALENDAR',
        ], fn (string $line): bool => $line !== ''));
    }

    private function buildEvent(EventOccurrence $occurrence): string
    {
        $event = $occurrence->event;

        return implode("\r\n", array_filter([
            'BEGIN:VEVENT',
            'UID:event-' . $event->id . '-occurrence-' . $occurrence->id . '@capell',
            'DTSTAMP:' . $this->formatDate(CarbonImmutable::now()),
            'DTSTART:' . $this->formatDate(CarbonImmutable::instance($occurrence->starts_at)),
            $occurrence->ends_at ? 'DTEND:' . $this->formatDate(CarbonImmutable::instance($occurrence->ends_at)) : null,
            'SUMMARY:' . $this->escapeText($event->name),
            isset($occurrence->location['name']) ? 'LOCATION:' . $this->escapeText((string) $occurrence->location['name']) : null,
            $event->pageUrl?->exists ? 'URL:' . $this->escapeText($event->pageUrl->full_url) : null,
            'END:VEVENT',
        ], fn (?string $line): bool => $line !== null && $line !== ''));
    }

    private function formatDate(CarbonImmutable $date): string
    {
        return $date->utc()->format('Ymd\THis\Z');
    }

    private function escapeText(string $text): string
    {
        return str_replace(
            ['\\', "\r\n", "\n", ',', ';'],
            ['\\\\', '\\n', '\\n', '\\,', '\\;'],
            $text,
        );
    }
}
