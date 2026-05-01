<?php

declare(strict_types=1);

namespace Capell\Events\Observers;

use Capell\Events\Actions\SyncEventOccurrencesAction;
use Capell\Events\Models\Event;

class EventObserver
{
    public function saved(Event $event): void
    {
        if ($this->shouldSyncOccurrences($event)) {
            SyncEventOccurrencesAction::run($event);
        }

        $this->clearCache();
    }

    public function deleted(Event $event): void
    {
        $this->clearCache();
    }

    public function restored(Event $event): void
    {
        $this->clearCache();
    }

    private function clearCache(): void {}

    private function shouldSyncOccurrences(Event $event): bool
    {
        if (! isset($event->meta['schedule']) || ! is_array($event->meta['schedule'])) {
            return false;
        }

        if (! isset($event->meta['schedule']['starts_at'])) {
            return false;
        }

        return $event->wasRecentlyCreated || $event->wasChanged('meta');
    }
}
