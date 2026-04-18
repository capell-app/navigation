# Mosaic Database Reference

Mosaic ships three tables of its own and alters one core table. This page
walks through each so you know what lives where.

## Migrations

| File | Effect |
| --- | --- |
| `database/migrations/create_sections_table.php` | Creates `sections`. |
| `database/migrations/create_widgets_table.php` | Creates `widgets`. |
| `database/migrations/create_widget_assets_table.php` | Creates `widget_assets`. |
| `database/migrations/add_container_widgets_to_layouts_table.php` | Adds container / widget JSON columns to the core `layouts` table. |

Run them all at once with `php artisan capell:mosaic-install`, or use
`php artisan migrate` after the package is registered.

## `sections`

Reusable content records — the "text and media" of the system. A section is
workspace-scoped, translatable, and hierarchical (stored as a nested set so
sections can be grouped into trees).

Key columns: `id`, `workspace_id`, `site_id`, `type_id`, `parent_id`,
`lft`, `rgt`, `name`, `order`, `meta` (JSON), visibility dates, userstamps,
and soft deletes. Translatable fields are stored through the Capell
translations layer.

How it behaves:

- **Workspace-aware** (`BelongsToWorkspace`) — live rows use
  `workspace_id = 0`; unpublished edits live on non-zero workspace copies.
- **Translatable** — translatable fields hydrate through the Capell
  translations layer.
- **Nested set** — `parent_id` plus `lft` / `rgt` give it a tree shape,
  which the admin uses to group related sections.
- **Publishable** — visibility is controlled by status columns and optional
  start / end dates.
- **Typeable** — `type_id` points at a Capell `Type` whose schema defines
  the editable fields on the section.
- **Cloneable** — works with the Capell page and widget clone actions.

> This is the table formerly known as `contents`. The model is
> `Capell\Mosaic\Models\Section`, but the relationship names on `Page`,
> `Site`, and `Type` are still `contents()` — the legacy label was kept so
> existing integrations don't break.

## `widgets`

A placed UI component instance — what the editor drops onto the page.

Key columns: `id`, `workspace_id`, `site_id`, `type_id`, `key` (unique per
workspace), `status`, `order`, `meta` (JSON), userstamps, soft deletes.

How it behaves:

- Workspace-aware, translatable, publishable, typeable, and cloneable —
  the same traits as sections, minus the nested set and asset slots.
- `key` is a human-readable handle used by layout JSON and by content
  lookups, so it has to stay stable for the life of the widget.
- Widgets are _placed_ on a layout, but their container and occurrence
  live in the layout JSON (see below) — not on this table.

## `widget_assets`

A polymorphic join between a widget and another record — usually a media
item or a section — with a position slot so one widget can hold many
references in order.

Key columns: `id`, `workspace_id`, `widget_id`, `asset_id`, `asset_type`,
`pageable_id`, `pageable_type`, `container`, `occurrence`, `order`.

Anything that holds multiple child records — a carousel's slides, a
feature grid's cards, a testimonial set — goes through this table.

## `layouts` (altered)

`add_container_widgets_to_layouts_table` adds JSON columns to the core
`layouts` table so each layout can store:

- Its container tree — rows, columns, and nested containers.
- The widget graph — which widget key lives at which container and
  occurrence.

Together, these are what `Layout::layoutWidgets()` exposes.

## Factories

Every model ships a matching factory in `database/factories/`:

- `SectionFactory`
- `WidgetFactory`
- `WidgetAssetFactory`

Use them in tests and seeders:

```php
use Capell\Mosaic\Models\Section;

$section = Section::factory()->for($site)->create(['name' => 'Welcome']);
```

## Runtime relationships

Registered in `MosaicServiceProvider` via `Model::resolveRelationUsing()` —
no generated code, no extra migrations:

- `Page::contents()` — sections reached through `widget_assets` rows that
  belong to the page.
- `Page::widgets()` — widgets placed on the page's layout.
- `Page::widgetAssets()` — the polymorphic join rows for those widgets.
- `Site::contents()` — every section on the site.
- `Type::contents()` / `Type::widgets()` — type-scoped accessors.
- `Layout::layoutWidgets()` — the widget graph stored on the layout's JSON
  column (resolved through the JSON relation, not a foreign key).

## See also

- [Mosaic API reference](mosaic-api.md)
- [Packages & add-ons](packages.md#mosaic-builder)
