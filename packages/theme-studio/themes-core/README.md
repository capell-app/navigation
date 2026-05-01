# Capell Themes Core

**Product group:** Capell Theme Studio
**Tier:** Premium

Themes Core is the shared toolkit behind Capell's frontend themes. Install it directly only when you are building a custom theme; the bundled SaaS, Corporate, and Agency themes pull it in automatically.

## When to install it

Install Themes Core when a custom theme needs shared utilities for accessibility, analytics, preview links, structured data, responsive images, search, forms, or theme settings.

## Quick install

```bash
composer require capell-app/themes-core
php artisan optimize:clear
php artisan test --filter=ThemesCore
```

The service provider is discovered automatically.

## What appears in the admin

Nothing by itself. Themes Core is infrastructure. Pair it with `capell-app/themes-admin` to expose the theme settings page in Filament.

## What developers get

| Module        | Useful for                                    |
| ------------- | --------------------------------------------- |
| Accessibility | ARIA helpers and WCAG contrast checks         |
| Analytics     | GA4 script helpers and UTM capture            |
| Forms         | Honeypot and Cloudflare Turnstile helpers     |
| Images        | Responsive `srcset` and `sizes` builders      |
| Preview       | Signed preview URLs for draft pages           |
| SEO           | Structured data, canonical URLs, social cards |
| Search        | Database and Scout-backed site search         |
| Performance   | Critical CSS and asset optimization helpers   |

## Example

```php
use Capell\Themes\Core\Accessibility\ContrastChecker;

$checker = new ContrastChecker;
$ratio = $checker->ratio('#ffffff', '#0e1b4c');

if (! $checker->meetsAA($ratio)) {
    throw new RuntimeException('Brand colors do not meet AA contrast.');
}
```

## Tests

```bash
php -d memory_limit=-1 vendor/bin/pest packages/theme-studio/themes-core/tests
```
