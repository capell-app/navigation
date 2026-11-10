# Navigation

<!-- prettier-ignore-start -->

## What It Does

Navigation adds site- and language-scoped menus with page links, external links, nested items, publication state, and reusable handles.

- **Price and availability:** Check current availability and price on the [Capell Marketplace listing](https://capell.app/extensions/navigation).
- **Licence:** Check the licence terms on the [Capell Marketplace listing](https://capell.app/extensions/navigation).
- **Requires:** `PHP ^8.4`, `capell-app/admin ^1.0.66`, `capell-app/core ^1.0.75`, `capell-app/frontend ^1.0.72`.
- **Install:** `composer require capell-app/navigation`.

Editors build and order menus in admin, and public themes receive hydrated navigation with safe URLs, active states, and nested children.

## Who It Is For

**For developers:** The navigable and handle registries let packages contribute linkable records and named menus without reaching into Navigation internals.

**For teams:** Editors can keep site menus current, reorder links, and reuse the same menu structure across theme output without code changes.

## Quick Start

1. Install the package: `composer require capell-app/navigation`.
2. Run the package setup: `php artisan capell:navigation-setup`.
3. See it working: run `php artisan capell:navigation-demo`.
4. Open the package admin surface at `/admin/navigation/navigations` and confirm Navigation is available.

## Configure

- **Configuration files:** `config/capell-navigation.php`.
- **Commands:** `php artisan capell:navigation-demo`, `php artisan capell:navigation-setup`, `php artisan capell:navigation:screenshot-fixture`.

## Limits And Pitfalls

- Install the required Capell packages at the versions in `composer.json`: `capell-app/admin ^1.0.66`, `capell-app/core ^1.0.75`, `capell-app/frontend ^1.0.72`.
- Run migrations before opening package resources or public routes.
- Review middleware, throttling, signatures, and public-output safety in `routes/web.php` before exposing routes.
- Keep public Blade and cached HTML free of authoring markers, model IDs, permissions, signed editor URLs, and lazy database queries.
- Custom write integrations must preserve invalidation for `navigation` cache tags.

## Screens And Workflow

![Navigation admin index](docs/screenshots/navigation-admin-index.png)

![Create/edit navigation form on desktop](docs/screenshots/create-edit-navigation-form-desktop.png)

- Navigation admin index (admin).
- Create/edit navigation form on desktop (admin).
- Create/edit navigation form on tablet (admin).
- Create/edit navigation form on mobile (admin).
- Site relation manager for navigations (admin).
- Page form navigation tab (admin).
- Landing page configuration tabs (admin).
- Frontend menu output (frontend).
- Navigation admin index with admin sidebar menu open (admin).

## Install Impact

- Required packages: `capell-app/admin`, `capell-app/core`, `capell-app/frontend`.
- Admin navigation: declares `admin-resource: NavigationAdminResourceContribution`; each Filament page or resource controls its own navigation visibility.
- Admin/editor extensions: `configurator: NavigationConfiguratorContribution`, `configurator: NavigationContentGraphContribution`, `configurator: NavigationFrontendRuntimeContribution`, `schema-extender: NavigationSchemaExtendersContribution`.
- Permissions: `ViewAny:Navigation`, `View:Navigation`, `Create:Navigation`, `Update:Navigation`, `Delete:Navigation`, `DeleteAny:Navigation`, `Restore:Navigation`, `RestoreAny:Navigation`, `ForceDelete:Navigation`, `ForceDeleteAny:Navigation`, `Reorder:Navigation`; access also governed by package policies: `NavigationPolicy`.
- Public routes: loads `routes/web.php`; registers `NavigationFrontendRouteContribution`.
- Database changes: package migrations are declared.
- Config: `config/capell-navigation.php`.
- Settings: no package settings declared.
- Queues or schedules: none declared.
- Cache tags: `navigation`.
- Commands: `capell:navigation-demo`, `capell:navigation-setup`, `capell:navigation:screenshot-fixture`.

## Troubleshooting

| Symptom | Likely cause | Check | Fix |
| --- | --- | --- | --- |
| Package surface is missing after install | Provider or manifest is not loaded | Confirm `capell.json`, package `composer.json`, and provider registration | Reinstall the package, refresh Composer autoload, and clear host caches |
| Admin screen or command fails on missing table | Package migrations have not run | Check the tables listed in `Data Model` | Run host migrations and rerun the focused package test |
| Route returns unexpected output | Route cache, middleware, or signed URL setup does not match the package route file | Check the route files listed in `Technical Shape` | Clear route cache and verify middleware before exposing public routes |
| Public output leaks unexpected state | Render data, cache variation, or authoring boundary has regressed | Check public Blade, cache tags, and public-output safety tests | Move data loading out of Blade and rerun the package public-output tests |

## Upgrading

No manual steps between minor versions.

Read [CHANGELOG.md](CHANGELOG.md) before upgrading.

## Next Steps

- [Package docs](docs/README.md)
- [Overview](docs/overview.md)
- [Worked extension examples](docs/extension-contracts.md)
- [Admin guide](docs/admin-guide.md)
- Configuration files: [`config/capell-navigation.php`](config/capell-navigation.php).
- [Troubleshooting](#troubleshooting)
- [Developer reference](docs/reference.md)
- [Marketplace assets](docs/assets/marketplace/)
- [Capell content language plan](../../docs/CONTENT_LANGUAGE_PLAN.md)
- [Capell documentation design system](../../docs/DESIGN_SYSTEM.md)
- [Capell and package ERD notes](../../docs/erd/capell-and-package-erds.md)
- Related packages: [Foundation Theme](../theme-foundation/README.md).

<!-- prettier-ignore-end -->
