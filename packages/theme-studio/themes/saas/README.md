# Capell SaaS Theme

**Product group:** Capell Theme Studio
**Tier:** Premium

The SaaS theme gives Capell a conversion-focused marketing frontend: hero sections, feature matrices, pricing, integrations, testimonials, FAQ, and CTA blocks.

## When to install it

Install this theme for software, subscription, platform, or product-led marketing sites that need polished landing pages quickly.

## Quick install

```bash
composer require capell-app/capell-theme-saas
php artisan migrate
php artisan saas:install --seed-layouts
```

Then open **Settings -> Theme** and choose **SaaS**.

## What appears in the admin

| Area           | What editors can do                                                                |
| -------------- | ---------------------------------------------------------------------------------- |
| Theme settings | Select SaaS and tune brand colors                                                  |
| Mosaic widgets | Use SaaS-specific hero, pricing, FAQ, testimonials, integrations, and CTA blocks   |
| Layouts        | Start from seeded home, pricing, and feature layouts when `--seed-layouts` is used |

## What developers get

- Tailwind-based responsive theme components.
- JSON-LD for Organization, SoftwareApplication, Product, FAQPage, and BreadcrumbList.
- Dark mode and `prefers-reduced-motion` support.
- Accessible landmarks, skip links, and ARIA labels.

## Deeper docs

| Doc                                    | Covers                                        |
| -------------------------------------- | --------------------------------------------- |
| [Installation](docs/INSTALLATION.md)   | Full install, publishing assets, uninstalling |
| [Customization](docs/CUSTOMIZATION.md) | CSS tokens, typography, spacing, dark mode    |
| [Components](docs/COMPONENTS.md)       | SaaS widgets and component props              |
