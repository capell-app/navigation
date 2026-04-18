# Content-Sync & Recovery Center

Content-Sync is the package-based export / import pipeline for Capell content. The **Recovery Center** is the Filament admin entry point for importing packages back into a target workspace.

> **A note on naming.** The admin page is currently labelled "Recovery Center" but behaves as a **content-import wizard** — the name predates the current scope. A future release may rename the page to "Content Import"; the documentation will follow.

See the full technical plan at [`docs/internal/recovery-center-and-developer-resource-plan.md`](internal/recovery-center-and-developer-resource-plan.md). This page is an end-user / operator overview of what has shipped.

---

## Package format

A exchanger package is a self-contained archive containing:

- `manifest.json` — package metadata, content list, per-entity ownership map, dependency graph.
- `integrity.json` — checksums for every payload file in the archive, used to verify the archive before import.
- `payload/` — serialized entities and their media references.

`PackageWriter` builds the archive; `PackageReader` verifies integrity on read and refuses to open a tampered archive.

## Export (H1)

Export actions are available as Filament bulk and row actions on the Pages resource and as a bulk action on Sites:

- **Pages** — single or bulk export selected pages (and their dependent rows) to a downloadable archive.
- **Sites** — export everything under a site.

Under the hood:

- `OwnershipMap` classifies every row as *owned* / *referenced* / *external* relative to the export scope.
- `DependencyGraphBuilder` walks relationships to pull in required related rows (page parents, shared media, etc.).
- `PayloadSerializer` writes per-entity payloads; `PageExportService` orchestrates the end-to-end flow.

## Import (H2.0) — Recovery Center

The **Recovery Center → Import Pages** Filament page is a Shield-gated admin surface for importing a previously exported package:

1. Upload the package archive.
2. Pick the target **draft workspace** — imports never touch live directly.
3. Run the import.

Under the hood:

- `PackageReader` verifies `integrity.json` before any row is touched.
- `ManifestValidator` checks manifest schema and version compatibility.
- `MatchResolver` (pluggable contract) maps package entities onto existing rows:
  - `KeyedMatchResolver` — matches by stable keys (uuid, slug, handle).
  - `MediaMatchResolver` — matches media by hash and file name.
- `ResolutionMapBuilder` produces the full mapping used by the import orchestrator.
- `PageImportService` runs the import **transactionally** into the chosen draft workspace.

Once imported, pages flow through the normal editorial pipeline — review them in the workspace, submit for approval, and publish as you would any other workspace edit.

## `ImportSession`

Every import creates an `ImportSession` row with its manifest, resolution map, and status. This is the audit record for "what got brought in and where it landed."

`ImportSessionKind` currently declares (not all wired yet — see H2.1+ in the plan):

- `PageImport` ✅ shipped in H2.0.
- `SiteImport`, `WordPressImport`, `SpreadsheetImport`, `FullRestore` — scaffolded, not yet wired.

## Known gaps (H2.1 continuation)

Tracked in §6 of the plan:

- **Correctness** — `parent_id` remap, `page_urls` restore, media-binary ingest when a referenced media row has no local match.
- **UX** — current wizard is a single form; the plan's Review / Resolve / Validate / Execute steps ship in H2.1.
- **Async** — import currently runs inline in the Livewire request; `ExecuteImportPlanJob` + a `exchanger` queue are slated for H2.1.
- **Permissions / notifications / audit list** — dedicated admin surfacing lands in H2.1.
- **Tests** — DB-backed integration tests for resolvers and orchestrator are blocked on a pre-existing faker `company` locale issue (§6.1); 17 unit tests are passing.

## Related files

| Concern | File |
| --- | --- |
| Ownership | `packages/core/src/Exchanger/Policy/OwnershipMap.php`, `Enums/RelationOwnership.php` |
| Export services | `packages/core/src/Exchanger/Services/Export/{PageExportService,PackageWriter,PayloadSerializer,DependencyGraphBuilder}.php` |
| Import services | `packages/core/src/Exchanger/Services/Import/{PackageReader,ManifestValidator,ResolutionMap,ResolutionMapBuilder,PageImportService}.php` |
| Resolvers | `packages/core/src/Exchanger/Services/Import/Resolvers/{MatchResolver,KeyedMatchResolver,MediaMatchResolver,MatchResolution}.php` |
| Data | `packages/core/src/Exchanger/Data/{PackageManifest,ExportOptions,DependencyGraph}.php` |
| Session | `packages/core/src/Exchanger/Models/ImportSession.php`, `Enums/{ImportSessionKind,ImportSessionStatus,PackageType}.php` |
| Admin | `packages/admin/src/Filament/Pages/ImportPagesPage.php` (Shield-gated) |
| Lang | `packages/admin/resources/lang/**/exchanger.php` |
| Technical plan | [`docs/internal/recovery-center-and-developer-resource-plan.md`](internal/recovery-center-and-developer-resource-plan.md) |
