# Navigation

Navigation owns editor-managed menus and renders structured navigation data for Capell frontend themes.

## At A Glance

- Package: `capell-app/navigation`
- Namespace: `Capell\Navigation\`
- Surfaces: Filament admin, console, database
- Service providers: `packages/navigation/src/Providers/NavigationServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`, `capell-app/frontend`

## Why It Helps Your Capell Workflow

- Adds site and language scoped navigation trees so editors can manage menus without editing theme code.
- Keeps page navigation fields, sync actions, and frontend loaders together for predictable theme rendering.
- Helps developers connect themes to editor-managed menus through package-owned loaders instead of direct page queries in Blade.

## Best Used With

- [Foundation Theme](../foundation-theme/README.md)
- [Layout Builder](../layout-builder/README.md)
- [Frontend Authoring](../frontend-authoring/README.md)

## What It Adds

Navigation adds site and language scoped navigation trees, page navigation fields, sync actions, and frontend loading support.

- Navigation Filament resource.
- Navigation relation manager on sites.
- Page schema extender for navigation placement.
- Navigation item model resolution.
- Indexed page-reference tracking for fast page edit panels.
- Actions to add, remove, replicate, and resolve navigation entries.
- Navigation loader support for frontend rendering.

## Why It Matters

**For developers:** Stores navigation items in structured data and uses adapters/registries to connect navigable models without hard-coding page logic everywhere.

**For teams:** Lets editors manage menus for each site and language while keeping page selection tied to Capell records.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Frontend](https://github.com/capell-app/frontend)

**Open-source packages used here**

- No extra third-party Composer package beyond the Capell package stack is required here.

## Screens And Workflow

Runtime screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment. Marketplace card and hero artwork ship from [docs/assets/marketplace](docs/assets/marketplace).

- Navigation admin index, light and dark.
- Create/edit navigation form, light and dark.
- Site relation manager for navigations, light and dark.
- Page form navigation tab, light and dark.
- Frontend menu output, light and dark.

## Technical Shape

- NavigationServiceProvider registers the package.
- Migrations create navigations and the indexed page-reference table.
- Model: Navigation.
- Filament resource: NavigationResource.
- Policy: NavigationPolicy.
- Events/listeners handle creation and site replication.

## Code Map

| Area      | Path                                | Purpose                                                             |
| --------- | ----------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/navigation/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/navigation/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/navigation/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/navigation/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/navigation/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| Providers | `packages/navigation/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/navigation/resources`     | Views, translations, assets, and package resources.                 |
| Database  | `packages/navigation/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/navigation/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Resources: `NavigationResource`.
- Pages: `CreateNavigation`, `EditNavigation`, `ListNavigations`.

## Commands

- `capell:navigation-demo {--sites=} {--languages=}` (packages/navigation/src/Console/Commands/DemoCommand.php)
- `capell:navigation-setup {--sites=}` (packages/navigation/src/Console/Commands/SetupCommand.php)

## Data And Persistence

- navigations stores key, type, site, language, items JSON, meta, and visibility windows.
- navigation_page_references stores the page references extracted from nested navigation items so admin page panels do not scan JSON.
- Navigation items may reference pages and page URLs through JSON.
- Navigations connect to sites, languages, and types.
- Cache key enum indicates navigation cache behaviour.

- Models: `Navigation`.
- Migrations: `2026_05_10_190860_01_create_navigations_table.php`, `2026_06_04_000001_create_navigation_page_references_table.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `NavigationNamesResolver`, `NavigationPageSyncer`.
- Events: `NavigationCreating`.
- Listeners: `ReplicateSiteNavigationsListener`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Adds navigations and navigation_page_references tables.
- Adds navigation admin resource and site relation manager.
- Extends page and site admin schemas.
- No explicit public route is registered by this package.
- Adds setup and demo commands.

## Install And Setup

- Install with `composer require capell-app/navigation` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Admin And Access

- NavigationResource (packages/navigation/src/Filament/Resources/Navigations/NavigationResource.php)
- CreateNavigation (packages/navigation/src/Filament/Resources/Navigations/Pages/CreateNavigation.php)
- EditNavigation (packages/navigation/src/Filament/Resources/Navigations/Pages/EditNavigation.php)
- ListNavigations (packages/navigation/src/Filament/Resources/Navigations/Pages/ListNavigations.php)

- Policy: NavigationPolicy (packages/navigation/src/Policies/NavigationPolicy.php)

## Common Pitfalls

- Create language/site records before creating scoped navigation.
- Keep navigation writes going through package actions/models so the page-reference index stays synchronized.
- Resolve stale page references after deleting pages.
- Clear navigation cache after manual data changes.

## Docs

- [docs index](docs/README.md)
- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [overview.md](docs/overview.md)
- [rendering-and-sync.md](docs/rendering-and-sync.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/navigation/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
