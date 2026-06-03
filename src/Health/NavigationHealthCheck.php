<?php

declare(strict_types=1);

namespace Capell\Navigation\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;
use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class NavigationHealthCheck implements ChecksExtensionHealth
{
    private const string NavigationMorphAlias = 'navigation';

    private const string StorageTableName = 'navigations';

    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }

    /**
     * @return Collection<int, DoctorCheckResultData>
     */
    public static function runDiagnostics(): Collection
    {
        $check = new self;

        return collect([
            $check->storageTableCheck(),
            $check->modelMorphAliasCheck(),
            $check->headerRenderHookCheck(),
        ]);
    }

    public static function passed(): bool
    {
        return self::runDiagnostics()
            ->every(static fn (DoctorCheckResultData $result): bool => $result->passed);
    }

    /**
     * Asserts the navigation storage table exists.
     */
    public function storageTableCheck(): DoctorCheckResultData
    {
        $tableExists = $this->hasStorageTable();

        return new DoctorCheckResultData(
            label: 'Navigation storage table',
            passed: $tableExists,
            message: $tableExists
                ? 'The navigations table is present.'
                : 'The navigations table is missing.',
            remediation: $tableExists
                ? null
                : 'Run the Capell migrations to create the navigations table.',
        );
    }

    /**
     * Asserts the Navigation model is discoverable through the morph map.
     */
    public function modelMorphAliasCheck(): DoctorCheckResultData
    {
        $aliasRegistered = $this->hasNavigationMorphAlias();

        return new DoctorCheckResultData(
            label: 'Navigation model morph alias',
            passed: $aliasRegistered,
            message: $aliasRegistered
                ? 'The Navigation model is registered in the morph map.'
                : 'The Navigation model is not registered in the morph map.',
            remediation: $aliasRegistered
                ? null
                : 'Ensure NavigationServiceProvider registers the Navigation model via CapellCore::registerModels().',
        );
    }

    /**
     * Asserts the foundation header navigation render hook is registered.
     */
    public function headerRenderHookCheck(): DoctorCheckResultData
    {
        $hookRegistered = $this->hasHeaderRenderHook();

        return new DoctorCheckResultData(
            label: 'Navigation header render hook',
            passed: $hookRegistered,
            message: $hookRegistered
                ? 'The foundation header navigation render hook is registered.'
                : 'The foundation header navigation render hook is not registered.',
            remediation: $hookRegistered
                ? null
                : 'Ensure NavigationServiceProvider registers the foundation header navigation render hook.',
        );
    }

    public function hasStorageTable(): bool
    {
        return Schema::hasTable(self::StorageTableName);
    }

    public function hasNavigationMorphAlias(): bool
    {
        return Relation::getMorphedModel(self::NavigationMorphAlias) === Navigation::class;
    }

    public function hasHeaderRenderHook(): bool
    {
        if (! app()->bound(RenderHookRegistry::class)) {
            return false;
        }

        try {
            $registry = app()->make(RenderHookRegistry::class);

            return $registry->get(RenderHookLocation::HeaderAfter) !== [];
        } catch (Throwable) {
            return false;
        }
    }
}
