<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\SeoTools\Contracts\SearchConsoleClientInterface;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static array{synced: int, configured: bool, pages: array<int, mixed>} run(int $siteId, int $limit = 10)
 */
final class SyncSearchConsoleInsightsAction
{
    use AsAction;

    /**
     * @return array{synced: int, configured: bool, pages: array<int, mixed>}
     */
    public function handle(int $siteId, int $limit = 10): array
    {
        $client = resolve(SearchConsoleClientInterface::class);

        if (! $client->isConfigured()) {
            return [
                'synced' => 0,
                'configured' => false,
                'pages' => [],
            ];
        }

        $pages = $client->decliningPages($siteId, $limit);

        return [
            'synced' => count($pages),
            'configured' => true,
            'pages' => $pages,
        ];
    }
}
