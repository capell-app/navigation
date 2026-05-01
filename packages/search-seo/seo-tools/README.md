# Capell SEO Tools

**Product group:** Capell Search & SEO
**Tier:** Premium

SEO Tools gives Capell sites the discoverability layer most CMS builds leave until too late: XML sitemaps, social metadata, JSON-LD, robots controls, `llms.txt`, and AI-assisted title and description suggestions.

## When to install it

Install SEO Tools when editors need to manage how pages appear in search, social previews, AI discovery tools, and structured data outputs.

## Quick install

```bash
composer require capell-app/seo-tools
php artisan capell:seo-tools-install
php artisan capell:seo-tools-setup
```

The package registers through Laravel discovery. It depends on `capell-app/admin` and `capell-app/frontend`.

## What appears in the admin

| Area              | What editors can do                                          |
| ----------------- | ------------------------------------------------------------ |
| Page SEO fields   | Review and improve titles, descriptions, and social previews |
| Settings          | Configure AI-assisted SEO prompts, limits, and defaults      |
| Dashboard/widgets | Inspect AI usage and generation history when enabled         |

## What developers get

- Actions for social metadata, page/site schema, breadcrumbs, sitemaps, and `llms.txt`.
- `StructuredDataBuilder`, `CanonicalUrl`, `SocialCards`, and sitemap support classes.
- AI generation history models, settings, rate limiting, and event hooks.
- Extenders that add SEO and AI-assist controls to Capell admin forms.

## Configuration

The main config file is `config/capell-seo-tools.php`. Configure model defaults, prompt templates, rate limits, sitemap behavior, and provider settings there.

## Deeper docs

- [SEO metadata and discoverability](docs/seo-meta-and-discoverability.md)
- [Sitemaps](docs/sitemaps.md)
- [OpenAI / AI-assisted SEO integration](../../docs/openai-integration.md)
