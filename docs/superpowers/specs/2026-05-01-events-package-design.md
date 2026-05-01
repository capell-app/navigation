# Events Package Design

## Goal

Create a `capell-app/events` package for publishing event content in Capell. Events are custom page types similar to Blog articles: each event is a pageable, translatable, publishable content item with its own frontend detail page, admin resource, layout, URL, media, and metadata.

The package also adds event-specific browsing surfaces: upcoming listings, an interactive Livewire calendar with month navigation and selected-date listings, persisted recurring event occurrences, booking links, calendar feeds, and JSON-LD event schema output.

## Package Shape

The package follows the Blog package structure and Capell package boundaries.

- Namespace: `Capell\Events`
- Composer package: `capell-app/events`
- Providers:
    - `EventsServiceProvider` for shared model, page type, Livewire, Blade, package metadata, assets, and morph-map registration.
    - `AdminServiceProvider` for Filament resource registration, configurators, default page creation, and navigation integration.
    - `FrontendServiceProvider` for frontend routes, sitemap integration, and feed exposure.
    - `ConsoleServiceProvider` for install/setup/demo commands.
- Dependencies:
    - core
    - admin
    - frontend
    - mosaic
    - navigation
    - workspaces

Tags are not included in the first implementation. The package can add tags later using the same bridge pattern Blog uses, but events do not need tags to satisfy listings, calendar, feeds, or schema.

## Domain Model

`Capell\Events\Models\Event` implements `Pageable`, `PageCacheable`, `Publishable`, `Translatable`, `Typeable`, `Userstampable`, and `HasMedia`. It uses the same core model traits as Blog articles where they apply: workspace ownership, clone support, media, assets, metadata, translations, type handling, publish dates, activity logging, and soft deletes.

The `events` table stores general content and authoring data: `uuid`, workspace columns, `name`, `type_id`, `layout_id`, `site_id`, `meta`, visible dates, `order`, userstamps, timestamps, and soft deletes.

`Capell\Events\Models\EventOccurrence` stores concrete dates generated from an event schedule. It belongs to an Event and Site, and stores `starts_at`, `ends_at`, `timezone`, `status`, `location`, `booking`, `schema`, and `is_cancelled`. The occurrence row is intentionally denormalized enough for fast listing, filtering, feeds, and schema output.

## Structured Data

The package uses Spatie Laravel Data classes at every boundary.

- `EventScheduleData`: start/end datetimes, timezone, recurrence, generation window.
- `EventRecurrenceData`: frequency, interval, weekdays, month day, until date, count.
- `EventLocationData`: physical, online, or hybrid location details.
- `EventBookingData`: booking URL, label, and optional open/close dates.
- `EventCalendarDayData`: day state and occurrence count for calendar rendering.
- `EventSchemaData`: values needed for schema.org `Event` JSON-LD output.

Model JSON columns use Data casts such as `AsData` where the repo dependencies support it.

## Recurrence Design

Recurring events use authored rules plus persisted occurrences. The event stores its recurrence rule in `EventScheduleData`. `GenerateEventOccurrencesAction` expands that rule into `event_occurrences` rows within a bounded generation window. The default generation window is 18 months from the event start unless `generateUntil`, `until`, or `count` ends the series earlier.

This keeps frontend reads straightforward: upcoming listings query rows by `starts_at`, selected-date listings query rows by date range, month navigation queries rows within calendar bounds, iCal feeds stream rows, and JSON-LD schema can be generated per occurrence.

The first version supports none, daily, weekly, and monthly recurrence. It does not implement exception dates, split-series editing, or complex RRULE import.

## Admin Experience

The package registers an Event resource as a Capell page resource variation, following Blog article patterns.

Admin form sections:

- Content translations and page fields inherited from the default page configurator.
- Event schedule: start/end date and time, timezone, recurrence frequency, interval, weekday/monthly controls, and generation limits.
- Location: physical, online, or hybrid with venue/name, address, URL, and coordinates.
- Booking: booking URL, booking label, and optional open/close dates.
- SEO/schema: schema status, attendance mode inferred from location type, organizer, and performer fields.

The resource table adds event-specific columns: event title/name, next occurrence start date, location summary, recurrence summary, booking indicator, site, languages, and publish state.

Writes go through Actions. Resource pages and configurators only collect form state and call Actions.

## Frontend Experience

Each event has its own pageable detail page. The detail view exposes title/content, next occurrence date and time, timezone-aware date formatting, location, booking action, upcoming recurring occurrences, and JSON-LD event schema.

The default events page lists upcoming occurrences ordered by `starts_at`. It can render with a package-owned Livewire view when the shared results layout cannot group by date cleanly.

The calendar is a Livewire page component with current year/month, selected date, site, and language state. It supports previous month, next month, today, and select-date interactions. The month grid queries occurrences from the visible calendar window, including leading and trailing days needed to fill complete weeks.

The package exposes an iCalendar feed route for published upcoming occurrences. The feed is site-aware, emits stable `VEVENT` UIDs, and includes title, description, URL, location, start, end, timezone, status, and booking URL.

## Schema Output

`BuildEventSchemaAction` creates schema.org JSON-LD for event detail pages and occurrence-backed listings where needed. It outputs context, type, name, description, start/end dates, status, attendance mode, location, image, URL, offers when a booking URL exists, and organizer when configured.

The Blade output uses a small package-owned component or render hook, avoiding core/frontend changes.

## Default Setup

The setup command creates Event, Events index, and Calendar page types; Event, Events index, and Calendar layouts; a default Events page; and an optional Calendar page under Events.

Events requires Mosaic first because it uses layouts/widgets and follows the same page model as Blog.

## Testing

Primary tests cover manifest requirements, model contracts, morph map/page variation registration, occurrence generation, occurrence regeneration, upcoming listing order, calendar month building, selected-date filtering, iCal output, JSON-LD schema fields, and Filament resource registration gating.

Run `vendor/bin/pest packages/events/tests` for package tests and `composer preflight` before committing the completed implementation.

## Deferred Scope

The first implementation excludes recurrence exception dates, splitting a recurring series from a selected occurrence, external calendar import, paid booking flows, waitlists, capacity management, tag integration, and map rendering.
