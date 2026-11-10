<?php

declare(strict_types=1);

namespace Capell\Navigation\Providers;

use Capell\Core\Support\Migration\MigrationRootRegistry;
use Illuminate\Support\ServiceProvider;
use Override;

final class MigrationMetadataServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {

        $this->callAfterResolving(MigrationRootRegistry::class, static function (MigrationRootRegistry $registry): void {
            $registry->register('capell-app/navigation', __DIR__ . '/../../database/migrations', [
                '2026_05_10_190860_01_create_navigations_table' => 'navigations',
                '2026_06_04_000001_create_navigation_page_references_table' => 'navigation_page_references',
            ]);
        });
    }
}
