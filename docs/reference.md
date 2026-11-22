# Navigation reference

<!-- prettier-ignore-start -->

Developer reference for `capell-app/navigation`. Start with the [README](../README.md) for what the extension does and how to install it.

## Package Details

Navigation is an **Available** Capell package in the **Capell Essentials** product group. It ships as `capell-app/navigation` and extends these surfaces: admin, frontend, console.

- Status: Available
- Tier: free
- Bundle: foundation
- Composer package: `capell-app/navigation`
- Namespace: `Capell\Navigation`
- Theme key: not applicable

## Technical Shape

### Service providers

- `Capell\Navigation\Providers\NavigationServiceProvider`
- `Capell\Navigation\Providers\MigrationMetadataServiceProvider`
- `Capell\Navigation\Providers\ConsoleServiceProvider`

### Migrations

- `packages/navigation/database/migrations/2026_05_10_190860_01_create_navigations_table.php`
- `packages/navigation/database/migrations/2026_06_04_000001_create_navigation_page_references_table.php`
- `packages/navigation/database/migrations/2026_08_28_000003_change_navigation_visibility_to_datetime.php`
- `packages/navigation/database/migrations/2026_11_14_000001_scope_navigation_key_uniqueness_to_active_menus.php`

### Models

- `Navigation`

### Filament classes

- `TypeSelect`
- `NavigationSelect`
- `NavigationTab`
- `NavigationItemsColumn`
- `DefaultNavigationConfigurator`
- `NavigationPageSchemaExtender`
- `NavigationSiteExtender`
- `NavigationResource`
- `CreateNavigation`
- `EditNavigation`
- `ListNavigations`
- `NavigationForm`
- `NavigationsTable`
- `NavigationsRelationManager`

### Policies

- `NavigationPolicy`

### Extension contracts

- `NavigationNamesResolver`
- `NavigationPageSyncer`

### Events

- `NavigationCreating`

### Listeners

- `ReplicateSiteNavigationsListener`

### Actions

- `AddPageToNavigationAction`
- `ApplyNavigationSiteSpecAction`
- `BuildNavigationBreadcrumbListSchemaAction`
- `BuildNavigationBreadcrumbsAction`
- `BuildNavigationImpactPreviewAction`
- `BuildNavigationRenderModelAction`
- `BuildNavigationStarterItemsAction`
- `BuildPageNavigationReferencesAction`
- `CheckSavedNavigationItemsAction`
- `EnsureNavigationItemKeysAction`
- `ExportNavigationSiteSpecAction`
- `RemovePageFromNavigationAction`
- `ReplicateSiteNavigationsAction`
- `ResolveNavigationCacheExpiryAction`
- `ResolveNavigationItemModelsAction`
- `ResolvePublicNavigationPagesAction`
- `ScalarizeNavigationDataAction`
- `SeedNavigationScreenshotFixtureAction`
- `SyncNavigationPageReferencesAction`

### Data objects

- `NavigationItemAvailabilityData`
- `NavigationItemData`
- `NavigationItemRenderData`
- `NavigationRenderContextData`
- `NavigationRenderData`
- `NavigationStarterItemsData`
- `NavigationStarterRequestData`

### Command signatures

- `capell:navigation-demo`
- `capell:navigation-setup`
- `capell:navigation:screenshot-fixture`

### Console command classes

- `DemoCommand`
- `SeedNavigationScreenshotFixtureCommand`
- `SetupCommand`

### Manifest contributions

- `admin-resource: Capell\Navigation\Manifest\NavigationAdminResourceContribution`
- `configurator: Capell\Navigation\Manifest\NavigationConfiguratorContribution`
- `configurator: Capell\Navigation\Manifest\NavigationContentGraphContribution`
- `configurator: Capell\Navigation\Manifest\NavigationFrontendRuntimeContribution`
- `console-command: Capell\Navigation\Manifest\NavigationConsoleCommandsContribution`
- `frontend-component: Capell\Navigation\Manifest\NavigationFrontendComponentsContribution`
- `health-check: Capell\Navigation\Manifest\NavigationHealthContribution`
- `migration: Capell\Navigation\Manifest\NavigationMigrationsContribution`
- `model: Capell\Navigation\Manifest\NavigationModelsContribution`
- `page-type: Capell\Navigation\Manifest\NavigationPageTypeContribution`
- `render-hook: Capell\Navigation\Manifest\NavigationRenderHookContribution`
- `schema-extender: Capell\Navigation\Manifest\NavigationSchemaExtendersContribution`

### Health checks

- `Capell\Navigation\Health\NavigationHealthCheck`

### Blade views

- `packages/navigation/resources/views/components/breadcrumbs.blade.php`
- `packages/navigation/resources/views/components/header/main-navigation.blade.php`
- `packages/navigation/resources/views/components/header/menu/dropdown.blade.php`
- `packages/navigation/resources/views/components/header/menu/item.blade.php`
- `packages/navigation/resources/views/components/header/navigation.blade.php`
- `packages/navigation/resources/views/components/menu-items.blade.php`
- `packages/navigation/resources/views/components/menu.blade.php`
- `packages/navigation/resources/views/components/page/navigations.blade.php`
- `packages/navigation/resources/views/filament/forms/navigation-impact-preview.blade.php`
- `packages/navigation/resources/views/filament/forms/saved-items-check.blade.php`

### Cache tags

- `navigation`


## Data Model

- Required tables: `navigations`, `navigation_page_references`.
- Models: `Navigation`.
- Core record references in migrations: `sites via site_id`, `languages via language_id`.
- Migration files: `2026_05_10_190860_01_create_navigations_table.php`, `2026_06_04_000001_create_navigation_page_references_table.php`, `2026_08_28_000003_change_navigation_visibility_to_datetime.php`, `2026_11_14_000001_scope_navigation_key_uniqueness_to_active_menus.php`.
- Migration impact: run host migrations through the package install flow before opening package surfaces.
- Deletion/retention behaviour: migrations declare cascade-on-delete relationships; no timed pruning or retention schedule is declared in `capell.json`.

<!-- prettier-ignore-end -->
