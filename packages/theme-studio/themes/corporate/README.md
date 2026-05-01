# Capell Corporate Theme

**Product group:** Capell Theme Studio
**Tier:** Premium

The Corporate theme gives Capell a calm, trust-building frontend for B2B, professional services, institutions, and company websites.

## When to install it

Install this theme when the site needs clear service pages, team content, case studies, contact flows, and accessible brand presentation without starting from a blank Blade theme.

## Quick install

```bash
composer require capell-app/capell-theme-corporate
php artisan migrate
php artisan corporate:install --seed-layouts
```

Then open **Settings -> Theme** and choose **Corporate**.

## What appears in the admin

| Area           | What editors can do                                                              |
| -------------- | -------------------------------------------------------------------------------- |
| Theme settings | Select Corporate and tune brand colors                                           |
| Mosaic widgets | Use corporate hero, features, team, case study, and contact blocks               |
| Layouts        | Start from seeded home, about, and contact layouts when `--seed-layouts` is used |

## What developers get

- Editorial layout patterns with accessible components.
- Dark mode via `prefers-color-scheme`.
- Contact form support with honeypot spam protection.
- Theme docs for tokens, typography, spacing, and component overrides.

## Deeper docs

| Doc                                    | Covers                                        |
| -------------------------------------- | --------------------------------------------- |
| [Installation](docs/INSTALLATION.md)   | Full install, publishing assets, uninstalling |
| [Customization](docs/CUSTOMIZATION.md) | CSS tokens, typography, spacing, dark mode    |
| [Components](docs/COMPONENTS.md)       | Corporate widgets and component props         |
