# Changelog

All notable changes to `capell-app/navigation` will be documented in this file.

## Unreleased

- Prepared package metadata and documentation for ongoing Capell 4.x package work.

## 2026-06-03

- Replaced the stub `NavigationHealthCheck` with real Diagnostics probes (storage table present, Navigation model registered in the morph map, foundation header render hook registered) so the critical health check no longer reports false-green.
- Populated manifest `capabilities[]` and `cacheSafety.invalidationSources[]` so dependent packages can declare a contract against navigation and tooling can see its cache invalidation triggers.
- Added `capell-app/core` to the composer `require` block to make the existing direct dependency explicit.
- Declared the shipped marketplace screenshots and hero assets in `marketplace.screenshots[]` (previously only one preview image was advertised).
- Rewrote the marketplace summary and composer description to be outcome-focused.
