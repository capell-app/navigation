# Capell Agency Theme

**Product group:** Capell Theme Studio
**Tier:** Premium

The Agency theme gives Capell a bold frontend for design studios, marketing teams, creative portfolios, and case-study-led sites.

## When to install it

Install this theme when the site needs expressive landing pages, portfolio grids, case studies, client logos, and strong visual sections managed from Capell.

## Quick install

```bash
composer require capell-app/capell-theme-agency
php artisan migrate
php artisan agency:install --seed-layouts
```

Then open **Settings -> Theme** and choose **Agency**.

## What appears in the admin

| Area           | What editors can do                                                             |
| -------------- | ------------------------------------------------------------------------------- |
| Theme settings | Select Agency and tune brand colors                                             |
| Mosaic widgets | Use agency hero, portfolio, logo marquee, case study, and CTA blocks            |
| Layouts        | Start from seeded home, work, and contact layouts when `--seed-layouts` is used |

## What developers get

- Responsive Blade and Tailwind theme components.
- Portfolio and case-study patterns.
- Dark mode via `prefers-color-scheme`.
- Accessible components that target WCAG 2.1 AA.

## Deeper docs

| Doc                                    | Covers                                           |
| -------------------------------------- | ------------------------------------------------ |
| [Installation](docs/INSTALLATION.md)   | Full install, publishing assets, uninstalling    |
| [Customization](docs/CUSTOMIZATION.md) | CSS tokens, dark mode, spacing, widget overrides |
| [Components](docs/COMPONENTS.md)       | Agency widgets and component props               |
