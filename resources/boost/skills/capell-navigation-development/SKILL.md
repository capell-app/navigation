---
name: capell-navigation-development
description: Use when editing Capell Navigation trees, page navigation fields, or frontend loading.
---

# Capell Navigation

Site and language scoped navigation trees, page fields, sync actions, and frontend lookup.

## Look

- `packages/navigation/src`
- `packages/navigation/docs`
- `packages/navigation/README.md`

## Rules

- Keep navigation scoped by site and language.
- Resolve page links through adapters instead of hard-coding URL logic.
- Preserve sync/import actions when changing item shape.
- Run `vendor/bin/pest packages/navigation/tests`.
