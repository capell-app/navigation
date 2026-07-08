# Navigation

<!-- prettier-ignore-start -->

## What This Extension Adds

Navigation is an **Available**, **Schema-owning** Capell package in the **Capell Foundation** product group. It ships as `capell-app/navigation` and extends these surfaces: admin, frontend, console.

Site- and language-scoped navigation menus for Capell: visual menu builder, page & link items, nested dropdowns, active-state rendering, publish scheduling, and multi-site replication.

After install, admins get package-owned management surfaces and public users may see package-owned frontend output or routes.

Status details:

- Status: Available
- Tier: free
- Bundle: foundation
- Composer package: `capell-app/navigation`
- Namespace: `Capell\Navigation`
- Theme key: not applicable

## Why It Matters

**For developers:** The package gives developers package-owned service providers, Actions, Data objects, models, Laravel routes, Filament classes, and Blade views instead of pushing this behaviour into core or application code.

**For teams:** Build and manage multilingual, per-site menus visually - link to any page or URL, nest dropdowns, and render them in your theme with one tag. Active-state, publish windows, and site cloning included.

## Screens And Workflow

Screenshot contract: `docs/screenshots.json`.

- Navigation admin index (admin, required).
- Create/edit navigation form (admin, required).
- Site relation manager for navigations (admin, required).
- Page form navigation tab (admin, required).
- Frontend menu output (frontend, required).

## Technical Shape

- Service providers: `Capell\Navigation\Providers\NavigationServiceProvider`.
- Migrations: `packages/navigation/database/migrations/2026_05_10_190860_01_create_navigations_table.php`, `packages/navigation/database/migrations/2026_06_04_000001_create_navigation_page_references_table.php`.
- Models: `Navigation`.
- Filament classes: `TypeSelect`, `NavigationSelect`, `NavigationTab`, `NavigationItemsColumn`, `DefaultNavigationConfigurator`, `NavigationPageSchemaExtender`, `NavigationSiteExtender`, `NavigationResource`, `CreateNavigation`, `EditNavigation`, `ListNavigations`, `NavigationForm`, `and 2 more`.
- Route files: `packages/navigation/routes/web.php`.
- Policies: `NavigationPolicy`.
- Events: `NavigationCreating`.
- Listeners: `ReplicateSiteNavigationsListener`.
- Actions: `AddPageToNavigationAction`, `BuildNavigationBreadcrumbsAction`, `BuildNavigationChildFragmentAction`, `BuildNavigationRenderModelAction`, `BuildPageNavigationReferencesAction`, `EnsureNavigationItemKeysAction`, `RemovePageFromNavigationAction`, `ReplicateSiteNavigationsAction`, `ResolveNavigationItemModelsAction`, `SyncNavigationPageReferencesAction`.
- Data objects: `NavigationItemData`, `NavigationItemRenderData`, `NavigationRenderContextData`, `NavigationRenderData`.
- Command signatures: `capell:navigation-demo`, `capell:navigation-setup`.
- Console command classes: `DemoCommand`, `SetupCommand`.
- Manifest contributions: `admin-resource: Capell\Navigation\Manifest\NavigationAdminResourceContribution`, `configurator: Capell\Navigation\Manifest\NavigationConfiguratorContribution`, `configurator: Capell\Navigation\Manifest\NavigationContentGraphContribution`, `configurator: Capell\Navigation\Manifest\NavigationFrontendRuntimeContribution`, `console-command: Capell\Navigation\Manifest\NavigationConsoleCommandsContribution`, `frontend-component: Capell\Navigation\Manifest\NavigationFrontendComponentsContribution`, `health-check: Capell\Navigation\Manifest\NavigationHealthContribution`, `migration: Capell\Navigation\Manifest\NavigationMigrationsContribution`, `model: Capell\Navigation\Manifest\NavigationModelsContribution`, `page-type: Capell\Navigation\Manifest\NavigationPageTypeContribution`, `render-hook: Capell\Navigation\Manifest\NavigationRenderHookContribution`, `route: Capell\Navigation\Manifest\NavigationFrontendRouteContribution`, `schema-extender: Capell\Navigation\Manifest\NavigationSchemaExtendersContribution`.
- Health checks: `Capell\Navigation\Health\NavigationHealthCheck`.
- Blade views: `packages/navigation/resources/views/components/breadcrumbs.blade.php`, `packages/navigation/resources/views/components/header/main-navigation.blade.php`, `packages/navigation/resources/views/components/header/menu/dropdown.blade.php`, `packages/navigation/resources/views/components/header/menu/item.blade.php`, `packages/navigation/resources/views/components/header/navigation.blade.php`, `packages/navigation/resources/views/components/menu-items.blade.php`, `packages/navigation/resources/views/components/menu.blade.php`, `packages/navigation/resources/views/components/page/navigations.blade.php`.
- Cache tags: `navigation`.

## Data Model

- Required tables: `navigations`, `navigation_page_references`.
- Models: `Navigation`.
- Migration files: `2026_05_10_190860_01_create_navigations_table.php`, `2026_06_04_000001_create_navigation_page_references_table.php`.
- Migration impact: run host migrations through the package install flow before opening package surfaces.
- Deletion/retention behaviour: Docs gap unless the package has an explicit pruning command, retention setting, or tested cascade path.

## Install Impact

- Admin navigation: adds package-owned Filament classes when registered.
- Permissions: none declared in `capell.json`.
- Public routes: route files exist and must be reviewed before public enablement.
- Database changes: package migrations are declared.
- Settings: no package settings declared.
- Queues or schedules: none detected in standard package paths.
- Cache tags: `navigation`.
- Commands: `capell:navigation-demo`, `capell:navigation-setup`.

## Common Pitfalls

- Run migrations before opening package resources or public routes.
- Review route middleware, throttling, signed URLs, and public-output safety before exposing routes.
- Keep public Blade and cached HTML free of authoring markers, model IDs, permissions, signed editor URLs, and lazy database queries.
- Run package commands from the host app; in this repository use `vendor/bin/pest` for package tests.
- Keep `composer.json`, `composer.local.json`, `capell.json`, docs, screenshots, and tests aligned when the package surface changes.

## Troubleshooting

| Symptom | Likely cause | Check | Fix |
| --- | --- | --- | --- |
| Package surface is missing after install | Provider or manifest is not loaded | Confirm `capell.json`, package `composer.json`, and provider registration | Reinstall the package, refresh Composer autoload, and clear host caches |
| Admin screen or command fails on missing table | Package migrations have not run | Check the tables listed in `Data Model` | Run host migrations and rerun the focused package test |
| Route returns unexpected output | Route cache, middleware, or signed URL setup does not match the package route file | Check the route files listed in `Technical Shape` | Clear route cache and verify middleware before exposing public routes |
| Background work does not run | Queue worker or scheduled command is not active | Check package jobs, commands, and host scheduler configuration | Start the queue or scheduler, then run the focused command or package test |
| Public output leaks unexpected state | Render data, cache variation, or authoring boundary has regressed | Check public Blade, cache tags, and public-output safety tests | Move data loading out of Blade and rerun the package public-output tests |

## Quick Start

1. Install the package: `composer require capell-app/navigation`.
2. Run the required setup: `php artisan capell:navigation-setup`.
3. Open the related Capell admin surface and verify Navigation appears.

## Next Steps

- [Package docs](docs/README.md)
- [Overview](docs/overview.md)
- [Screenshot contract](docs/screenshots.json)
- [Marketplace assets](docs/assets/marketplace/)
- [Capell content language plan](../../docs/CONTENT_LANGUAGE_PLAN.md)
- [Capell documentation design system](../../docs/DESIGN_SYSTEM.md)
- [Capell and package ERD notes](../../docs/erd/capell-and-package-erds.md)
- Focused tests: `vendor/bin/pest packages/navigation/tests --configuration=phpunit.xml`.

<!-- prettier-ignore-end -->
