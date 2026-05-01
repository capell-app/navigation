<?php

declare(strict_types=1);

namespace Capell\Events\Observers;

use Capell\Events\Models\Event;

class EventObserver
{
    public function saved(Event $event): void
    {
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
}
