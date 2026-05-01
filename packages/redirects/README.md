# Capell Redirects

Redirects manages manual 301/302 redirects, automatic page-slug redirects, CSV import/export, and broken URL reporting for Capell.

The package keeps canonical storage in Core's `page_urls` table and adds package-owned behaviour around redirect validation, admin management, frontend resolution, and broken-link tracking.

## When to install it

Install Redirects when editors need a redirect manager, when slug changes should create redirects automatically, or when the site needs to record unresolved URLs for later cleanup.

## Quick install

```bash
composer require capell-app/redirects
php artisan migrate
php artisan optimize:clear
```

## What appears in the admin

| Area        | What editors can do                                        |
| ----------- | ---------------------------------------------------------- |
| Redirects   | Create, edit, filter, import, and export manual redirects  |
| Broken URLs | Review URLs reported by the frontend resolver when enabled |

## What developers get

- `RedirectResolver`, `RedirectRecorder`, and `BrokenUrlReporter` contracts.
- Automatic redirect creation when page URLs change.
- Validation for duplicates, self-redirects, loops, and redirect chains.
- Filament importer/exporter classes for CSV workflows.
- Config toggles for automatic redirects and broken URL reporting.

## Reference

See [Redirect Manager reference](docs/redirects.md) for storage, validation, admin, frontend, and configuration details.
