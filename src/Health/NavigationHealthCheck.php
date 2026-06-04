<?php

declare(strict_types=1);

namespace Capell\Navigation\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;
use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class NavigationHealthCheck implements ChecksExtensionHealth
{
    private const string NavigationMorphAlias = 'navigation';

    /**
     * @var list<string>
     */
    private const array StorageTableNames = [
        'navigations',
        'navigation_page_references',
    ];

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
            $check->mainNavigationCoverageCheck(),
            $check->pageReferenceIntegrityCheck(),
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
        $missingTables = $this->missingStorageTables();

        return new DoctorCheckResultData(
            label: (string) __('capell-navigation::generic.health_storage_tables_label'),
            passed: $missingTables === [],
            message: $missingTables === []
                ? (string) __('capell-navigation::generic.health_storage_tables_passed')
                : (string) __('capell-navigation::generic.health_storage_tables_failed', ['tables' => implode(', ', $missingTables)]),
            remediation: $missingTables === []
                ? null
                : (string) __('capell-navigation::generic.health_storage_tables_remediation'),
        );
    }

    /**
     * Asserts the Navigation model is discoverable through the morph map.
     */
    public function modelMorphAliasCheck(): DoctorCheckResultData
    {
        $aliasRegistered = $this->hasNavigationMorphAlias();

        return new DoctorCheckResultData(
            label: (string) __('capell-navigation::generic.health_morph_alias_label'),
            passed: $aliasRegistered,
            message: $aliasRegistered
                ? (string) __('capell-navigation::generic.health_morph_alias_passed')
                : (string) __('capell-navigation::generic.health_morph_alias_failed'),
            remediation: $aliasRegistered
                ? null
                : (string) __('capell-navigation::generic.health_morph_alias_remediation'),
        );
    }

    /**
     * Asserts the foundation header navigation render hook is registered.
     */
    public function headerRenderHookCheck(): DoctorCheckResultData
    {
        $hookRegistered = $this->hasHeaderRenderHook();

        return new DoctorCheckResultData(
            label: (string) __('capell-navigation::generic.health_header_render_hook_label'),
            passed: $hookRegistered,
            message: $hookRegistered
                ? (string) __('capell-navigation::generic.health_header_render_hook_passed')
                : (string) __('capell-navigation::generic.health_header_render_hook_failed'),
            remediation: $hookRegistered
                ? null
                : (string) __('capell-navigation::generic.health_header_render_hook_remediation'),
        );
    }

    /**
     * Asserts every site can resolve a main navigation.
     */
    public function mainNavigationCoverageCheck(): DoctorCheckResultData
    {
        if (! Schema::hasTable('sites') || ! Schema::hasTable('navigations')) {
            return new DoctorCheckResultData(
                label: (string) __('capell-navigation::generic.health_main_navigation_coverage_label'),
                passed: false,
                message: (string) __('capell-navigation::generic.health_main_navigation_coverage_missing_tables'),
                remediation: (string) __('capell-navigation::generic.health_main_navigation_coverage_missing_tables_remediation'),
            );
        }

        $missingSiteIds = $this->missingMainNavigationSiteIds();

        return new DoctorCheckResultData(
            label: (string) __('capell-navigation::generic.health_main_navigation_coverage_label'),
            passed: $missingSiteIds === [],
            message: $missingSiteIds === []
                ? (string) __('capell-navigation::generic.health_main_navigation_coverage_passed')
                : (string) __('capell-navigation::generic.health_main_navigation_coverage_failed', ['sites' => implode(', ', $missingSiteIds)]),
            remediation: $missingSiteIds === []
                ? null
                : (string) __('capell-navigation::generic.health_main_navigation_coverage_remediation'),
        );
    }

    /**
     * Asserts the navigation page reference table points at existing pageable records.
     */
    public function pageReferenceIntegrityCheck(): DoctorCheckResultData
    {
        if (! Schema::hasTable('navigation_page_references')) {
            return new DoctorCheckResultData(
                label: (string) __('capell-navigation::generic.health_page_reference_integrity_label'),
                passed: false,
                message: (string) __('capell-navigation::generic.health_page_reference_integrity_missing_table'),
                remediation: (string) __('capell-navigation::generic.health_page_reference_integrity_missing_table_remediation'),
            );
        }

        $orphanedReferenceCount = $this->orphanedPageReferenceCount();

        return new DoctorCheckResultData(
            label: (string) __('capell-navigation::generic.health_page_reference_integrity_label'),
            passed: $orphanedReferenceCount === 0,
            message: $orphanedReferenceCount === 0
                ? (string) __('capell-navigation::generic.health_page_reference_integrity_passed')
                : (string) __('capell-navigation::generic.health_page_reference_integrity_failed', ['count' => $orphanedReferenceCount]),
            remediation: $orphanedReferenceCount === 0
                ? null
                : (string) __('capell-navigation::generic.health_page_reference_integrity_remediation'),
        );
    }

    public function hasStorageTable(): bool
    {
        return $this->missingStorageTables() === [];
    }

    /**
     * @return list<string>
     */
    public function missingStorageTables(): array
    {
        return array_values(collect(self::StorageTableNames)
            ->reject(static fn (string $tableName): bool => Schema::hasTable($tableName))
            ->values()
            ->all());
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

    /**
     * @return list<int>
     */
    public function missingMainNavigationSiteIds(): array
    {
        if (! Schema::hasTable('sites') || ! Schema::hasTable('navigations')) {
            return [];
        }

        if ($this->globalMainNavigationExists()) {
            return [];
        }

        $missingSiteIds = DB::table('sites')
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn (mixed $siteId): int => (int) $siteId)
            ->reject(fn (int $siteId): bool => $this->siteMainNavigationExists($siteId))
            ->values()
            ->all();

        return array_values($missingSiteIds);
    }

    public function hasMainNavigationForEverySite(): bool
    {
        return $this->missingMainNavigationSiteIds() === [];
    }

    public function hasOrphanedPageReferences(): bool
    {
        return $this->orphanedPageReferenceCount() > 0;
    }

    public function orphanedPageReferenceCount(): int
    {
        if (! Schema::hasTable('navigation_page_references')) {
            return 0;
        }

        $orphanedReferenceCount = 0;
        $pageableTypes = DB::table('navigation_page_references')
            ->select(['pageable_type'])
            ->distinct()
            ->pluck('pageable_type');

        foreach ($pageableTypes as $pageableType) {
            if (! is_string($pageableType) || $pageableType === '') {
                $orphanedReferenceCount += $this->referenceCountForPageableType($pageableType);

                continue;
            }

            $modelClass = Relation::getMorphedModel($pageableType) ?? $pageableType;

            if (! is_string($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                $orphanedReferenceCount += $this->referenceCountForPageableType($pageableType);

                continue;
            }

            /** @var class-string<Model> $modelClass */
            $orphanedReferenceCount += $this->orphanedReferenceCountForModel($pageableType, $modelClass);
        }

        return $orphanedReferenceCount;
    }

    private function globalMainNavigationExists(): bool
    {
        return DB::table('navigations')
            ->where('key', NavigationHandle::Main->value)
            ->whereNull('site_id')
            ->whereNull('deleted_at')
            ->exists();
    }

    private function siteMainNavigationExists(int $siteId): bool
    {
        return DB::table('navigations')
            ->where('key', NavigationHandle::Main->value)
            ->where('site_id', $siteId)
            ->whereNull('deleted_at')
            ->exists();
    }

    /**
     * @return list<int>
     */
    private function pageableReferenceIds(string $pageableType): array
    {
        $pageableIds = DB::table('navigation_page_references')
            ->where('pageable_type', $pageableType)
            ->pluck('pageable_id')
            ->map(static fn (mixed $pageableId): int => (int) $pageableId)
            ->unique()
            ->values()
            ->all();

        return array_values($pageableIds);
    }

    private function referenceCountForPageableType(mixed $pageableType): int
    {
        return DB::table('navigation_page_references')
            ->where('pageable_type', $pageableType)
            ->count();
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function orphanedReferenceCountForModel(string $pageableType, string $modelClass): int
    {
        $model = new $modelClass;
        $pageableIds = $this->pageableReferenceIds($pageableType);

        if ($pageableIds === []) {
            return 0;
        }

        $existingIds = $modelClass::query()
            ->whereIn($model->getKeyName(), $pageableIds)
            ->pluck($model->getKeyName())
            ->map(static fn (mixed $pageableId): int => (int) $pageableId)
            ->all();
        $orphanedIds = array_diff($pageableIds, $existingIds);

        if ($orphanedIds === []) {
            return 0;
        }

        return DB::table('navigation_page_references')
            ->where('pageable_type', $pageableType)
            ->whereIn('pageable_id', $orphanedIds)
            ->count();
    }
}
