# Capell Themes Admin

**Product group:** Capell Theme Studio
**Tier:** Premium

Themes Admin adds the Filament settings screen for Capell themes. It lets administrators choose the active theme and change brand colors from the admin panel.

## When to install it

Install Themes Admin when your project uses Capell theme packages or a custom theme that should be selectable from Settings.

## Quick install

```bash
composer require capell-app/themes-admin
php artisan optimize:clear
php artisan capell:static-site
```

## What appears in the admin

| Area              | What administrators can do                        |
| ----------------- | ------------------------------------------------- |
| Settings -> Theme | Pick the active theme                             |
| Theme settings    | Set primary and accent brand colors               |
| Extended schemas  | Show extra fields registered by individual themes |

## Requirements

| Tool     | Version                 |
| -------- | ----------------------- |
| PHP      | 8.2+                    |
| Laravel  | 11.44.2+, 12.x, or 13.x |
| Filament | 4.7+ or 5.2+            |

`capell-app/themes-core` is installed automatically.

## Extend the settings schema

```php
use Capell\Themes\Admin\Schemas\ThemeSettingsSchema;
use Filament\Forms\Components\Toggle;

ThemeSettingsSchema::extend(function (array $components): array {
    return array_merge($components, [
        Toggle::make('show_cookie_banner')->label('Show cookie banner'),
    ]);
});
```

## Tests

```bash
php -d memory_limit=-1 vendor/bin/pest packages/theme-studio/themes-admin/tests
```
