# Content Scheduler Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expand the existing workspaces scheduled publishing page into a prominent Content Scheduler with workspace publish/unpublish dates, embargoes, review reminders, and calendar-ready scheduler rows.

**Architecture:** Keep the feature in `packages/workspaces`. Mutations live in focused Actions, read models live in Data and report Actions, and Filament pages/widgets only render the resulting scheduler state.

**Tech Stack:** PHP 8.2, Laravel, Filament, Pest, Spatie Laravel Data, Lorisleiva Actions.

---

## File Map

- Create `packages/workspaces/database/migrations/add_content_scheduler_columns_to_workspaces_table.php`: adds `unpublish_at`, `embargo_until`, and `review_reminder_at`.
- Modify `packages/workspaces/src/Models/Workspace.php`: fillable, docblocks, and immutable casts for scheduler metadata.
- Create `packages/workspaces/src/Data/SchedulerEventData.php`: normalized scheduler row for mixed workspace/page events.
- Create `packages/workspaces/src/Enums/SchedulerEventTypeEnum.php`: publish, unpublish, embargo, review reminder labels/colors.
- Create `packages/workspaces/src/Actions/SetWorkspaceSchedulerMetadataAction.php`: set or clear workspace scheduler dates.
- Create `packages/workspaces/src/Actions/Reports/BuildContentSchedulerEventsAction.php`: combine workspace and page events into sorted Data rows.
- Create `packages/workspaces/src/Exceptions/EmbargoActiveException.php`: clear publish failure when embargo is active.
- Modify `packages/workspaces/src/Publisher.php`: block publishing before `embargo_until`.
- Modify `packages/workspaces/src/PublishScheduledWorkspacesJob.php`: leave embargoed scheduled work for a later tick.
- Modify `packages/workspaces/src/Filament/Pages/ScheduledPublishingPage.php`: relabel as Content Scheduler, move to content navigation, add badge/header widgets.
- Modify `packages/workspaces/src/Filament/Pages/Tables/ScheduledPublishingTable.php`: render scheduler event rows instead of page-only rows.
- Create `packages/workspaces/src/Filament/Widgets/ContentSchedulerOverviewWidget.php`: compact counts for upcoming publishes, unpublishes, embargoes, and reminders.
- Modify `packages/workspaces/src/Providers/AdminServiceProvider.php`: register translations and widget.
- Create `packages/workspaces/resources/lang/en/scheduler.php`: scheduler labels.
- Create and update Pest tests under `packages/workspaces/tests`.

## Tasks

### Task 1: Scheduler Metadata And Embargo Guard

- [ ] Write tests in `packages/workspaces/tests/Integration/WorkspaceSchedulerMetadataActionTest.php` for setting and clearing `unpublish_at`, `embargo_until`, and `review_reminder_at`.
- [ ] Write tests in `packages/workspaces/tests/Integration/PublisherEmbargoTest.php` proving `Publisher::publish()` throws `EmbargoActiveException` before `embargo_until` and publishes after the embargo expires.
- [ ] Add the workspace scheduler columns migration.
- [ ] Update `Workspace` fillable, casts, and docblock.
- [ ] Add `SetWorkspaceSchedulerMetadataAction`.
- [ ] Add `EmbargoActiveException` and call it from `Publisher` before the transaction.
- [ ] Update `PublishScheduledWorkspacesJob` to catch `EmbargoActiveException`.
- [ ] Run `vendor/bin/pest packages/workspaces/tests/Integration/WorkspaceSchedulerMetadataActionTest.php packages/workspaces/tests/Integration/PublisherEmbargoTest.php packages/workspaces/tests/Integration/PublishScheduledWorkspacesJobTest.php`.

### Task 2: Calendar-Ready Scheduler Event Read Model

- [ ] Write tests in `packages/workspaces/tests/Admin/Feature/Actions/Reports/BuildContentSchedulerEventsActionTest.php` covering page publish, page unpublish, workspace publish, workspace unpublish, embargo, and review reminder rows.
- [ ] Add `SchedulerEventTypeEnum` with translated labels and Filament colors.
- [ ] Add `SchedulerEventData`.
- [ ] Add `BuildContentSchedulerEventsAction` that returns a sorted collection of upcoming scheduler events.
- [ ] Keep the existing `BuildScheduledPublishingQueryAction` in place for compatibility, but use the new Action for the page.
- [ ] Run `vendor/bin/pest packages/workspaces/tests/Admin/Feature/Actions/Reports/BuildContentSchedulerEventsActionTest.php`.

### Task 3: Filament Content Scheduler Page And Table

- [ ] Update `ScheduledPublishingPageTest` to expect `Content Scheduler`, content/admin navigation prominence, a badge, and visibility of mixed scheduler rows.
- [ ] Update `ScheduledPublishingPage` labels, title, subheading, navigation group, badge query, and header widgets.
- [ ] Replace `ScheduledPublishingTable` columns with scheduler event columns: title, type, source, status, scheduled_for, description, and record action URL.
- [ ] Add filters for event type and source.
- [ ] Add `packages/workspaces/resources/lang/en/scheduler.php`.
- [ ] Register translations in `AdminServiceProvider`.
- [ ] Run `vendor/bin/pest packages/workspaces/tests/Admin/Feature/Filament/Pages/ScheduledPublishingPageTest.php`.

### Task 4: Overview Widget

- [ ] Write `packages/workspaces/tests/Feature/Filament/Widgets/ContentSchedulerOverviewWidgetTest.php`.
- [ ] Add `ContentSchedulerOverviewWidget` using `BuildContentSchedulerEventsAction`.
- [ ] Register the widget on the main dashboard and as a header widget on the page.
- [ ] Run `vendor/bin/pest packages/workspaces/tests/Feature/Filament/Widgets/ContentSchedulerOverviewWidgetTest.php`.

### Task 5: Final Verification

- [ ] Run `vendor/bin/pest packages/workspaces/tests`.
- [ ] Run `composer lint -- --test` if available; otherwise run `composer lint`.
- [ ] Run `composer analyze`.
- [ ] Review `git diff --stat` and ensure unrelated existing worktree changes are not staged.
