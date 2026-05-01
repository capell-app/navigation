<?php

declare(strict_types=1);

namespace Capell\Redirects\Support;

use Capell\Core\Models\BrokenLink;
use Capell\Redirects\Contracts\BrokenUrlReporter;

class DatabaseBrokenUrlReporter implements BrokenUrlReporter
{
    public function report(string $targetUrl, ?int $statusCode = null, ?int $pageId = null): void
    {
        if (! config('redirects.broken_urls.enabled', true)) {
            return;
        }

        if ($pageId === null) {
            return;
        }

        BrokenLink::query()->updateOrCreate(
            [
                'page_id' => $pageId,
                'target_url' => $targetUrl,
            ],
            [
                'http_status' => $statusCode,
                'last_checked_at' => now(),
            ],
        );
    }
}
