<?php

declare(strict_types=1);

namespace Capell\Redirects\Contracts;

class NullBrokenUrlReporter implements BrokenUrlReporter
{
    public function report(string $targetUrl, ?int $statusCode = null, ?int $pageId = null): void {}
}
