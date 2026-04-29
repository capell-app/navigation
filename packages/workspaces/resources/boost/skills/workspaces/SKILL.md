---
name: workspaces
description: Use when working on the Capell Workspaces package. Covers the Workspace/Version models, copy-on-write pattern, WorkspaceStatusEnum state machine, approval pipeline actions, rollback, PreviewLink, Filament resources, BelongsToWorkspace trait, and how to extend workspace lifecycle events.
---

# Capell Workspaces

Workspaces is a **draft/approval/versioning infrastructure** package — a meta-layer over the entire CMS. Any content model opts into draft workflows via the `BelongsToWorkspace` trait. Changes are isolated in a workspace, reviewed, approved, and published atomically as immutable `Version` snapshots.

**Package location:** `~/Sites/packages/capell/capell-packages-4/packages/workspaces`

---

## Core Concepts

### Workspace

An isolated editing context. Changes inside a workspace are invisible to live site visitors until published.

**Status machine:**

```
Open → InReview → Approved → Scheduled → Publishing → Published  (terminal)
     ↘                                                ↗
       Abandoned  (terminal)
```

- `isEditable()` — only `Open`
- `isTerminal()` — `Published` or `Abandoned`
- `isInApprovalPipeline()` — `InReview`, `Approved`, or `Scheduled`

### Version

An **immutable** snapshot of the live site state at a point in time.

- Exactly one Version has `is_live = true` at any moment
- `manifest` — maps model class → array of IDs that comprise the live state
- Publishing a workspace creates a new Version from the workspace's manifest, sets `is_live = true`
- Rollback creates a new Version from a prior Version's manifest
- **Never update a Version record** — they are append-only

### Copy-On-Write (COW)

The core mechanism — no snapshot is taken upfront. When a model is first edited inside a workspace:

1. `CopyOnWriteAction` clones the live row into workspace scope (`workspace_id` set on the clone)
2. The live row gets `shadowed_by_workspace_id` set (direct DB update, bypasses events)
3. Queries prefer the workspace clone over the live row when a workspace context is active
4. On publish: clones are promoted to live; shadows are lifted

This is handled automatically by the `BelongsToWorkspace` trait's `saving`/`deleting` event hooks. **Do not call `CopyOnWriteAction` directly** from application code unless building workspace infrastructure.

---

## Adding Workspace Support to a Model

Apply the `BelongsToWorkspace` trait and add two nullable FK columns:

```php
use Capell\Workspaces\Concerns\BelongsToWorkspace;

class MyModel extends Model
{
    use BelongsToWorkspace;
}
```

Migration columns:

```php
$table->foreignId('workspace_id')->nullable()->constrained('workspaces')->nullOnDelete();
$table->foreignId('shadowed_by_workspace_id')->nullable()->constrained('workspaces')->nullOnDelete();
```

---

## Key Models

### Workspace
- Fields: `uuid`, `name`, `slug`, `description`, `status`, `kind`, `base_version_id`, `publish_at`, `submitted_at`, `approved_at`, `published_at`, `settings` (WorkspaceSettingsData)
- Relationships:
  - `baseVersion()` — BelongsTo Version (state workspace branched from)
  - `publishedVersion()` — HasOne Version (resulting live version after publish)
  - `approvals()` — HasMany WorkspaceApproval (full history)
  - `latestApproval()` — HasOne WorkspaceApproval (most recent)

### Version
- Fields: `uuid`, `number`, `name`, `notes`, `is_live`, `manifest` (array), `source_workspace_id`, `published_at`, `published_by` (polymorphic)
- Key methods: `Version::liveId()`, `Version::currentLive()`, `Version::manifestIdsFor(ModelClass::class)`

### PreviewLink
- Temporary shareable URL for previewing workspace content without CMS login
- Managed via `GenerateWorkspacePreviewUrlAction`, `ExtendPreviewLinkAction`, `RevokePreviewLinkAction`

---

## Lifecycle Actions

| Action | Transition |
|--------|-----------|
| `SaveAsDraftAction` | Persists edits within Open workspace |
| `SubmitForApprovalAction` | Open → InReview |
| `ApproveAction` | InReview → Approved |
| `RequestChangesAction` | InReview → Open (with review comment) |
| `RejectAction` | InReview → Abandoned |
| `ScheduleAction` | Approved → Scheduled (sets `publish_at`) |
| `UnscheduleAction` | Scheduled → Approved |
| `PublishAction` | Approved → Published (creates new live Version) |
| `RollbackAction` | Creates new Version from a prior Version's manifest |
| `DiscardWorkspacesAction` | Open → Abandoned (bulk) |

---

## Dashboard Actions (Read-Only)

Return structured data only — do not mutate state:

| Action | Returns |
|--------|---------|
| `BuildSiteStatsAction` | Page/article counts |
| `BuildWorkspaceMergeHistoryAction` | Timeline of published versions |
| `BuildMyWorkQueueAction` | Items awaiting the current user's review |
| `BuildContentHealthAction` | SEO/translation/metadata quality scores |
| `BuildRecentlyPublishedAction` | Recent version snapshots |
| `BuildWorkspaceActivityAction` | Workspace state change log |
| `BuildStaleDraftsQueryAction` | Query for long-abandoned workspaces |
| `BuildScheduledPublishingQueryAction` | Upcoming scheduled publishes |
| `BuildActivityTrailQueryAction` | Full audit log |

---

## Filament Resources

### WorkspaceResource
- Pages: `ManageWorkspaces` (list/filter), `CompareVersionPage` (side-by-side diff via `jfcherng/php-diff`)
- Record actions: full lifecycle as Filament actions (Preview, Validate, Compare, SubmitForApproval, Approve, RequestChanges, Reject, Schedule, Unschedule, Publish, Rollback)
- Bulk action: `RequestReviewBulkAction`

### PreviewLinkResource
- Pages: `ManagePreviewLinks` — manage temporary share links, expiry, usage

### Page Extension (non-invasive extenders)
- `WorkspacesPageEditExtender` — adds workspace context to page edit form
- `WorkspacesPageTableExtender` — adds workspace status column to pages list
- `WorkspacesPageResourcePageExtender` — adds workspace actions to page resource header
- `WorkspacesPageExportExtender` — handles workspace-aware page exports

---

## Event System

- `WorkspaceStateChanged` — fired on every status transition
- `WorkspaceEventDispatcher` facade — hook into lifecycle events (`beforeDelete`, `afterDelete`)
- All workspace actions are audit-logged via Spatie ActivityLog

To react to workspace transitions:

```php
use Capell\Workspaces\Events\WorkspaceStateChanged;

class MyWorkspaceListener
{
    public function handle(WorkspaceStateChanged $event): void
    {
        $workspace = $event->workspace;
        $newStatus = $event->status;
        // react to transition
    }
}
```

Register in your service provider's `$listen` array.

---

## Commands

| Command | Purpose |
|---------|---------|
| `php artisan workspaces:install` | Package installation |

---

## Testing Workspaces

Test the full state machine with real DB — mocks will miss COW side effects:

```php
it('publishes workspace and creates a new live version', function () {
    $workspace = Workspace::factory()->approved()->create();

    PublishAction::run($workspace);

    expect($workspace->fresh()->status)->toBe(WorkspaceStatusEnum::Published)
        ->and(Version::currentLive())->source_workspace_id->toBe($workspace->id);
});

it('copy-on-write clones the live row into workspace scope', function () {
    $page = Page::factory()->published()->create();
    $workspace = Workspace::factory()->open()->create();

    // Edit page inside workspace context
    $workspace->activate();
    $page->update(['title' => 'Draft Title']);

    // Clone exists in workspace scope
    expect(Page::where('workspace_id', $workspace->id)->exists())->toBeTrue()
        // Live row is shadowed
        ->and($page->fresh()->shadowed_by_workspace_id)->toBe($workspace->id);
});
```
