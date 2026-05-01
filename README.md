# Capell Packages

First-party add-ons for [Capell CMS](https://github.com/capell-app/capell). Install only the packages your project needs: visual page building, blog publishing, SEO, themes, addresses, workspaces, and media alternatives all live here.

## Pick the package by job

| Need                                              | Package            | Composer package                                                                                      |
| ------------------------------------------------- | ------------------ | ----------------------------------------------------------------------------------------------------- |
| Visual page builder                               | Mosaic             | `capell-app/mosaic`                                                                                   |
| Articles, tags, archives, RSS                     | Blog               | `capell-app/blog`                                                                                     |
| Drafts, previews, approvals, scheduled publishing | Workspaces         | `capell-app/workspaces`                                                                               |
| Sitemaps, structured data, AI-assisted SEO        | SEO Tools          | `capell-app/seo-tools`                                                                                |
| Header, footer, and sidebar menus                 | Navigation         | `capell-app/navigation`                                                                               |
| Public site keyword search                        | Site Search        | `capell-app/site-search`                                                                              |
| 301/302 redirects and broken URL reporting        | Redirects          | `capell-app/redirects`                                                                                |
| Shared tagging across content types               | Tags               | `capell-app/tags`                                                                                     |
| Editor-managed forms and submissions              | Forms              | `capell-app/forms`                                                                                    |
| Campaign landing pages and conversion goals       | Campaigns          | `capell-app/campaigns`                                                                                |
| Content package export, import, and restore       | Backup             | `capell-app/backup`                                                                                   |
| Country and address fields                        | Address            | `capell-app/address`                                                                                  |
| Login and activity visibility                     | Authentication Log | `capell-app/authentication-log`                                                                       |
| Curator instead of Spatie MediaLibrary            | Media Curator      | `capell-app/media-curator`                                                                            |
| Default Tailwind/frontend pipeline                | Default Theme      | `capell-app/default-theme`                                                                            |
| Theme settings in Filament                        | Themes Admin       | `capell-app/themes-admin`                                                                             |
| SaaS, Corporate, or Agency frontend               | Themes             | `capell-app/capell-theme-saas`, `capell-app/capell-theme-corporate`, `capell-app/capell-theme-agency` |

## Common install pattern

Most packages follow this shape:

```bash
composer require capell-app/<package>
php artisan capell:<package>-install
php artisan capell:<package>-demo
```

Some packages auto-register through Laravel package discovery or have theme-specific commands. Check each package README for the exact commands.

## Recommended editorial stack

For a content-heavy site with pages, widgets, articles, approvals, and search metadata:

```bash
composer require capell-app/mosaic capell-app/blog capell-app/workspaces capell-app/seo-tools
php artisan capell:mosaic-install
php artisan capell:blog-install
```

Then configure SEO Tools and Workspaces from the Capell admin.

## Package notes

| Package            | What appears in the admin                                    |
| ------------------ | ------------------------------------------------------------ |
| Mosaic             | Contents, Widgets, Layouts, and visual builder fields        |
| Blog               | Articles, Tags, blog pages, archive pages                    |
| Workspaces         | Workspace switcher, approvals, preview links, publish checks |
| SEO Tools          | SEO settings, AI-assist panels, sitemap and metadata tools   |
| Navigation         | Menu builders and site/page navigation extenders             |
| Site Search        | Search contracts, database/Scout drivers, optional analytics |
| Redirects          | Redirect manager, CSV import/export, broken URL reports      |
| Tags               | Shared tag management used by packages such as Blog          |
| Forms              | Form and submission records                                  |
| Campaigns          | Campaign records, CTA blocks, conversion goals, reports      |
| Backup             | Import sessions, package validation, recovery workflows      |
| Address            | Countries, Addresses, Site address fields                    |
| Authentication Log | Dashboard authentication activity                            |
| Themes Admin       | Settings -> Theme                                            |
| Media Curator      | Curator picker fields when selected as the media backend     |

## Documentation

- Core docs: [docs.capell.app](https://docs.capell.app)
- Package registry: [Capell-approved packages](https://docs.capell.app/packages/)
- Per-package API and database references live beside each package under `packages/<name>/docs/`.

## License

Proprietary unless an individual package states otherwise.
