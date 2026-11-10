<?php

declare(strict_types=1);

namespace Capell\Navigation\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;
use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Capell\Core\Support\TableName;
use Capell\Frontend\Data\RenderHookContext;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\RenderHooks\RegisterFoundationHeaderNavigationHook;
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
        return '^1.0';
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
            id: 'legacy.navigation.storage.tables',
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
            id: 'legacy.navigation.model.morph.alias',
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
            id: 'legacy.navigation.header.render.hook',
        );
    }

    /**
     * Asserts every site can resolve a main navigation.
     */
    public function mainNavigationCoverageCheck(): DoctorCheckResultData
    {
        if (! Schema::hasTable(TableName::resolve('sites')) || ! Schema::hasTable(TableName::resolve('navigations'))) {
            return new DoctorCheckResultData(
                label: (string) __('capell-navigation::generic.health_main_navigation_coverage_label'),
                passed: false,
                message: (string) __('capell-navigation::generic.health_main_navigation_coverage_missing_tables'),
                remediation: (string) __('capell-navigation::generic.health_main_navigation_coverage_missing_tables_remediation'),
                id: 'legacy.navigation.main.menu.coverage',
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
            id: 'legacy.navigation.main.menu.coverage',
        );
    }

    /**
     * Asserts the navigation page reference table points at existing pageable records.
     */
    public function pageReferenceIntegrityCheck(): DoctorCheckResultData
    {
        if (! Schema::hasTable(TableName::resolve('navigation_page_references'))) {
            return new DoctorCheckResultData(
                label: (string) __('capell-navigation::generic.health_page_reference_integrity_label'),
                passed: false,
                message: (string) __('capell-navigation::generic.health_page_reference_integrity_missing_table'),
                remediation: (string) __('capell-navigation::generic.health_page_reference_integrity_missing_table_remediation'),
                id: 'legacy.navigation.page.reference.integrity',
            );
        }

        $orphanedReferenceCount = $this->orphanedPageReferenceCount();
        $affectedNavigationCount = $this->navigationsWithOrphanedPageReferencesCount();

        return new DoctorCheckResultData(
            label: (string) __('capell-navigation::generic.health_page_reference_integrity_label'),
            passed: $orphanedReferenceCount === 0,
            message: $orphanedReferenceCount === 0
                ? (string) __('capell-navigation::generic.health_page_reference_integrity_passed')
                : (string) __('capell-navigation::generic.health_page_reference_integrity_failed', ['menus' => $affectedNavigationCount, 'count' => $orphanedReferenceCount]),
            remediation: $orphanedReferenceCount === 0
                ? null
                : (string) __('capell-navigation::generic.health_page_reference_integrity_remediation'),
            id: 'legacy.navigation.page.reference.integrity',
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
        return array_values(collect(array_map(TableName::resolve(...), self::StorageTableNames))
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
            $registeredScenarios = $this->registeredNavigationHeaderHookScenarios($registry);

            return array_diff($this->requiredHeaderHookScenarios(), $registeredScenarios) === [];
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return list<int>
     */
    public function missingMainNavigationSiteIds(): array
    {
        if (! Schema::hasTable(TableName::resolve('sites')) || ! Schema::hasTable(TableName::resolve('navigations'))) {
            return [];
        }

        if ($this->globalMainNavigationExists()) {
            return [];
        }

        $missingSiteIds = DB::table(TableName::resolve('sites'))
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
        return count($this->orphanedReferenceNavigationIds());
    }

    /**
     * The number of menus that hold at least one orphaned page reference; these are the menus an
     * operator re-saves to repair the references.
     */
    public function navigationsWithOrphanedPageReferencesCount(): int
    {
        return count(array_unique($this->orphanedReferenceNavigationIds()));
    }

    /**
     * @return list<int> The owning navigation id of every orphaned page reference.
     */
    private function orphanedReferenceNavigationIds(): array
    {
        if (! Schema::hasTable(TableName::resolve('navigation_page_references'))) {
            return [];
        }

        $navigationIds = [];
        $pageableTypes = DB::table(TableName::resolve('navigation_page_references'))
            ->select(['pageable_type'])
            ->distinct()
            ->pluck('pageable_type');

        foreach ($pageableTypes as $pageableType) {
            if (! is_string($pageableType) || $pageableType === '') {
                $navigationIds = [...$navigationIds, ...$this->referenceNavigationIdsForPageableType($pageableType)];

                continue;
            }

            $modelClass = Relation::getMorphedModel($pageableType) ?? $pageableType;

            if (! is_string($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                $navigationIds = [...$navigationIds, ...$this->referenceNavigationIdsForPageableType($pageableType)];

                continue;
            }

            /** @var class-string<Model> $modelClass */
            $navigationIds = [...$navigationIds, ...$this->orphanedReferenceNavigationIdsForModel($pageableType, $modelClass)];
        }

        return $navigationIds;
    }

    private function globalMainNavigationExists(): bool
    {
        return DB::table(TableName::resolve('navigations'))
            ->where('key', NavigationHandle::Main->value)
            ->whereNull('site_id')
            ->whereNull('deleted_at')
            ->exists();
    }

    private function siteMainNavigationExists(int $siteId): bool
    {
        return DB::table(TableName::resolve('navigations'))
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
        $pageableIds = DB::table(TableName::resolve('navigation_page_references'))
            ->where('pageable_type', $pageableType)
            ->pluck('pageable_id')
            ->map(static fn (mixed $pageableId): int => (int) $pageableId)
            ->unique()
            ->values()
            ->all();

        return array_values($pageableIds);
    }

    /**
     * @return list<int>
     */
    private function referenceNavigationIdsForPageableType(mixed $pageableType): array
    {
        return $this->navigationIds(DB::table(TableName::resolve('navigation_page_references'))
            ->where('pageable_type', $pageableType)
            ->pluck('navigation_id')
            ->all());
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return list<int>
     */
    private function orphanedReferenceNavigationIdsForModel(string $pageableType, string $modelClass): array
    {
        $model = new $modelClass;
        $pageableIds = $this->pageableReferenceIds($pageableType);

        if ($pageableIds === []) {
            return [];
        }

        $existingIds = $modelClass::query()
            ->whereIn($model->getKeyName(), $pageableIds)
            ->pluck($model->getKeyName())
            ->map(static fn (mixed $pageableId): int => (int) $pageableId)
            ->all();
        $orphanedIds = array_diff($pageableIds, $existingIds);

        if ($orphanedIds === []) {
            return [];
        }

        return $this->navigationIds(DB::table(TableName::resolve('navigation_page_references'))
            ->where('pageable_type', $pageableType)
            ->whereIn('pageable_id', $orphanedIds)
            ->pluck('navigation_id')
            ->all());
    }

    /**
     * @param  array<array-key, mixed>  $navigationIds
     * @return list<int>
     */
    private function navigationIds(array $navigationIds): array
    {
        return array_values(array_map(static fn (mixed $navigationId): int => is_numeric($navigationId) ? (int) $navigationId : 0, $navigationIds));
    }

    /**
     * @param  RenderHookRegistry<RenderHookContext>  $registry
     * @return list<string>
     */
    private function registeredNavigationHeaderHookScenarios(RenderHookRegistry $registry): array
    {
        $extensions = $this->registeredRenderHookExtensions($registry);
        $headerExtensions = $extensions[RenderHookLocation::HeaderAfter->value] ?? [];

        if (! is_array($headerExtensions)) {
            return [];
        }

        $scenarios = [];

        foreach ($headerExtensions as $extensionEntry) {
            if (! is_array($extensionEntry)) {
                continue;
            }

            if (($extensionEntry['target'] ?? null) !== RegisterFoundationHeaderNavigationHook::Target) {
                continue;
            }

            $scenario = $extensionEntry['scenario'] ?? null;

            if (is_string($scenario) && in_array($scenario, $this->requiredHeaderHookScenarios(), true)) {
                $scenarios[] = $scenario;
            }
        }

        return array_values(array_unique($scenarios));
    }

    /**
     * @param  RenderHookRegistry<RenderHookContext>  $registry
     * @return array<string, mixed>
     */
    private function registeredRenderHookExtensions(RenderHookRegistry $registry): array
    {
        return $registry->diagnostics();
    }

    /**
     * @return list<string>
     */
    private function requiredHeaderHookScenarios(): array
    {
        return [
            RegisterFoundationHeaderNavigationHook::DefaultScenario,
            RegisterFoundationHeaderNavigationHook::FoundationScenario,
        ];
    }
}
