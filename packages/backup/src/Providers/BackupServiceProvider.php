<?php

declare(strict_types=1);

namespace Capell\Backup\Providers;

use Capell\Backup\Contracts\BackupContextResolver;
use Capell\Backup\Contracts\BackupRowContributor;
use Capell\Backup\Contracts\NullBackupContextResolver;
use Capell\Backup\Contracts\NullBackupRowContributor;
use Capell\Backup\Contracts\NullPageCollisionDetector;
use Capell\Backup\Contracts\PageCollisionDetector;
use Capell\Backup\Models\BackupRestore;
use Capell\Backup\Models\ImportSession;
use Capell\Backup\Services\Import\Resolvers\FingerprintMatchResolver;
use Capell\Backup\Services\Import\Resolvers\KeyedMatchResolver;
use Capell\Backup\Services\Import\Resolvers\MediaMatchResolver;
use Capell\Backup\Services\Import\Resolvers\RelationMatchResolverRegistry;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

class BackupServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'backup';

    public static string $packageName = 'capell-app/backup';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('backup')
            ->hasMigrations([
                'create_import_sessions_table',
                'drop_workspace_id_from_import_sessions_table',
                'create_backup_restores_table',
            ]);
    }

    public function packageRegistered(): void
    {
        CapellCore::registerModels([
            BackupRestore::class,
            ImportSession::class,
        ]);

        $this->app->singletonIf(BackupContextResolver::class, NullBackupContextResolver::class);
        $this->app->singletonIf(BackupRowContributor::class, NullBackupRowContributor::class);
        $this->app->singletonIf(PageCollisionDetector::class, NullPageCollisionDetector::class);

        $this->app->singleton(
            RelationMatchResolverRegistry::class,
            static function (): RelationMatchResolverRegistry {
                $registry = new RelationMatchResolverRegistry;
                $registry->register('layouts', new KeyedMatchResolver(Layout::class));
                $registry->register('layouts', new FingerprintMatchResolver(Layout::class));
                $registry->register('types', new KeyedMatchResolver(Type::class));
                $registry->register('types', new FingerprintMatchResolver(Type::class));
                $registry->register('sites', new KeyedMatchResolver(Site::class, keyColumn: 'slug'));
                $registry->register('media', new MediaMatchResolver);

                return $registry;
            },
        );
    }
}
