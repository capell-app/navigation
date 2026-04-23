# Capell Media

Media library admin UI for Capell. Ships the Filament `MediaResource` (listing, filters, replace-file action) that manages Spatie MediaLibrary records.

The underlying `Media` model, `InteractsWithMedia` trait, path generator, and base migration live in `capell-app/core`. This package provides only the admin-facing Filament resource.

## Install

```bash
composer require capell-app/media
```

The resource is auto-registered with the admin panel via `Capell\Media\Providers\AdminServiceProvider`.
