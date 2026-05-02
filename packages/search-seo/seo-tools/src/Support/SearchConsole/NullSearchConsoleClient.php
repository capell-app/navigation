<?php

declare(strict_types=1);

namespace Capell\SeoTools\Support\SearchConsole;

use Capell\SeoTools\Contracts\SearchConsoleClientInterface;

final class NullSearchConsoleClient implements SearchConsoleClientInterface
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function pageInsights(string $url): array
    {
        return [];
    }

    public function decliningPages(int $siteId, int $limit = 10): array
    {
        return [];
    }
}
