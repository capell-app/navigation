# Merge Hero Package into Mosaic Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Consolidate the hero package into mosaic by moving all hero code, updating namespaces, merging service providers, and removing the separate hero package.

**Architecture:** Hero currently depends on mosaic as a separate package. We'll merge it by: (1) copying hero files into mosaic under the Capell\Mosaic namespace, (2) merging migrations/configs/resources, (3) consolidating the service provider, (4) updating all internal references, (5) removing the hero package from the monorepo.

**Tech Stack:** Laravel 10.x, Pest, PHP 8.2, Filament 4.7+/5.2+

---

## File Structure & Decomposition

### Files to Move (hero → mosaic)

**PHP Source Code:**
- `packages/hero/src/Actions/*` → `packages/mosaic/src/Actions/Hero/*` (rename classes to avoid conflicts)
- `packages/hero/src/Enums/*` → `packages/mosaic/src/Enums/Hero/*`
- `packages/hero/src/Filament/Components/Forms/Page/HeroEditor.php` → `packages/mosaic/src/Filament/Components/Forms/Page/HeroEditor.php`
- `packages/hero/src/Filament/Extenders/Page/HeroPageSchemaExtender.php` → `packages/mosaic/src/Filament/Extenders/Page/HeroPageSchemaExtender.php`
- `packages/hero/src/Filament/Resources/Contents/Schemas/Types/HeroContentSchema.php` → `packages/mosaic/src/Filament/Resources/Contents/Schemas/Types/HeroContentSchema.php`
- `packages/hero/src/Filament/Resources/Widgets/Schemas/Types/HeroWidgetSchema.php` → `packages/mosaic/src/Filament/Resources/Widgets/Schemas/Types/HeroWidgetSchema.php`
- `packages/hero/src/View/Components/Widget/Hero.php` → `packages/mosaic/src/View/Components/Widget/Hero.php`
- `packages/hero/src/Console/Commands/*` → `packages/mosaic/src/Console/Commands/Hero/*`

**Resources:**
- `packages/hero/resources/views/components/hero/*` → `packages/mosaic/resources/views/components/hero/*`
- `packages/hero/resources/views/components/widget/hero.blade.php` → `packages/mosaic/resources/views/components/widget/hero.blade.php`
- `packages/hero/resources/views/components/pagination/summary.blade.php` → `packages/mosaic/resources/views/components/pagination/hero-summary.blade.php` (renamed to avoid conflicts)
- `packages/hero/resources/lang/en/*.php` → `packages/mosaic/resources/lang/en/` (merge with existing files)

**Tests:**
- `packages/hero/tests/*` → `packages/mosaic/tests/Hero/*`

**Migrations & Factories:**
- `packages/hero/database/migrations/*` → `packages/mosaic/database/migrations/`
- `packages/hero/database/factories/*` → `packages/mosaic/database/factories/`

### Files to Create

- `packages/mosaic/src/Filament/Extenders/Page/` directory (for hero extenders)

### Files to Modify

- `packages/mosaic/src/Providers/MosaicServiceProvider.php` - merge hero service provider into this
- `packages/mosaic/composer.json` - update description to include hero
- Root `composer.json` - remove hero package path if present
- `composer.local.json` - remove hero if listed
- Any files in other packages that import from `Capell\Hero` namespace

---

## Task Breakdown

### Task 1: Create directory structure in mosaic for hero code

**Files:**
- Create directories in `packages/mosaic/src/`

- [ ] **Step 1: Create Action subdirectory for hero actions**

```bash
mkdir -p packages/mosaic/src/Actions/Hero
```

- [ ] **Step 2: Create Enums subdirectory for hero enums**

```bash
mkdir -p packages/mosaic/src/Enums/Hero
```

- [ ] **Step 3: Create Console/Commands/Hero subdirectory**

```bash
mkdir -p packages/mosaic/src/Console/Commands/Hero
```

- [ ] **Step 4: Create Filament Extenders directory**

```bash
mkdir -p packages/mosaic/src/Filament/Extenders/Page
```

- [ ] **Step 5: Create View Components directory**

```bash
mkdir -p packages/mosaic/src/View/Components/Widget
```

---

### Task 2: Copy and namespace hero Actions

**Files:**
- Create: `packages/mosaic/src/Actions/Hero/CreateHeroContentTypeAction.php`
- Create: `packages/mosaic/src/Actions/Hero/CreateHeroWidgetAction.php`
- Create: `packages/mosaic/src/Actions/Hero/AddHeroWidgetToLayoutAction.php`
- Create: `packages/mosaic/src/Actions/Hero/HeroWidgetHasPrimaryHeadingAction.php`
- Delete: `packages/hero/src/Actions/*`

- [ ] **Step 1: Read original CreateHeroContentTypeAction**

```bash
cat packages/hero/src/Actions/CreateHeroContentTypeAction.php
```

- [ ] **Step 2: Create CreateHeroContentTypeAction in mosaic with updated namespace**

Update the namespace from `Capell\Hero\Actions` to `Capell\Mosaic\Actions\Hero`, copy full class content.

- [ ] **Step 3: Create CreateHeroWidgetAction in mosaic**

Copy from `packages/hero/src/Actions/CreateHeroWidgetAction.php`, update namespace.

- [ ] **Step 4: Create AddHeroWidgetToLayoutAction in mosaic**

Copy from `packages/hero/src/Actions/AddHeroWidgetToLayoutAction.php`, update namespace.

- [ ] **Step 5: Create HeroWidgetHasPrimaryHeadingAction in mosaic**

Copy from `packages/hero/src/Actions/HeroWidgetHasPrimaryHeadingAction.php`, update namespace.

- [ ] **Step 6: Verify no remaining hero action files**

```bash
ls -la packages/hero/src/Actions/
```

Expected: All .php files have been copied.

- [ ] **Step 7: Commit**

```bash
git add packages/mosaic/src/Actions/Hero/
git commit -m "feat: move hero actions into mosaic package"
```

---

### Task 3: Copy and namespace hero Enums

**Files:**
- Create: `packages/mosaic/src/Enums/Hero/ContentSchemaEnum.php`
- Create: `packages/mosaic/src/Enums/Hero/WidgetComponentEnum.php`
- Create: `packages/mosaic/src/Enums/Hero/WidgetSchemaEnum.php`
- Create: `packages/mosaic/src/Enums/Hero/WidgetTypeEnum.php`

- [ ] **Step 1: Copy ContentSchemaEnum**

Read `packages/hero/src/Enums/ContentSchemaEnum.php` and create in `packages/mosaic/src/Enums/Hero/ContentSchemaEnum.php` with namespace updated to `Capell\Mosaic\Enums\Hero`.

- [ ] **Step 2: Copy WidgetComponentEnum**

Read `packages/hero/src/Enums/WidgetComponentEnum.php` and create in `packages/mosaic/src/Enums/Hero/WidgetComponentEnum.php` with namespace updated.

- [ ] **Step 3: Copy WidgetSchemaEnum**

Read `packages/hero/src/Enums/WidgetSchemaEnum.php` and create in `packages/mosaic/src/Enums/Hero/WidgetSchemaEnum.php` with namespace updated.

- [ ] **Step 4: Copy WidgetTypeEnum**

Read `packages/hero/src/Enums/WidgetTypeEnum.php` and create in `packages/mosaic/src/Enums/Hero/WidgetTypeEnum.php` with namespace updated.

- [ ] **Step 5: Commit**

```bash
git add packages/mosaic/src/Enums/Hero/
git commit -m "feat: move hero enums into mosaic package"
```

---

### Task 4: Copy and namespace hero Filament components

**Files:**
- Create: `packages/mosaic/src/Filament/Components/Forms/Page/HeroEditor.php`
- Create: `packages/mosaic/src/Filament/Extenders/Page/HeroPageSchemaExtender.php`
- Create: `packages/mosaic/src/Filament/Resources/Contents/Schemas/Types/HeroContentSchema.php`
- Create: `packages/mosaic/src/Filament/Resources/Widgets/Schemas/Types/HeroWidgetSchema.php`

- [ ] **Step 1: Copy HeroEditor.php**

Read `packages/hero/src/Filament/Components/Forms/Page/HeroEditor.php`, create in mosaic with namespace updated to `Capell\Mosaic\Filament\Components\Forms\Page`, update any internal references from `Capell\Hero\*` to `Capell\Mosaic\*`.

- [ ] **Step 2: Copy HeroPageSchemaExtender.php**

Read `packages/hero/src/Filament/Extenders/Page/HeroPageSchemaExtender.php`, create in `packages/mosaic/src/Filament/Extenders/Page/HeroPageSchemaExtender.php` with namespace updated to `Capell\Mosaic\Filament\Extenders\Page`, update internal references.

- [ ] **Step 3: Copy HeroContentSchema.php**

Read `packages/hero/src/Filament/Resources/Contents/Schemas/Types/HeroContentSchema.php`, create with namespace updated, update internal references from hero to mosaic.

- [ ] **Step 4: Copy HeroWidgetSchema.php**

Read `packages/hero/src/Filament/Resources/Widgets/Schemas/Types/HeroWidgetSchema.php`, create with namespace updated, update internal references from hero to mosaic.

- [ ] **Step 5: Update all internal references in Filament files**

Search for `use Capell\Hero` in the newly created files and replace with `use Capell\Mosaic`:

```bash
grep -r "Capell\\Hero" packages/mosaic/src/Filament/Components/Forms/Page/HeroEditor.php
grep -r "Capell\\Hero" packages/mosaic/src/Filament/Extenders/Page/HeroPageSchemaExtender.php
grep -r "Capell\\Hero" packages/mosaic/src/Filament/Resources/Contents/Schemas/Types/HeroContentSchema.php
grep -r "Capell\\Hero" packages/mosaic/src/Filament/Resources/Widgets/Schemas/Types/HeroWidgetSchema.php
```

Expected: No matches.

- [ ] **Step 6: Commit**

```bash
git add packages/mosaic/src/Filament/Components/Forms/Page/HeroEditor.php
git add packages/mosaic/src/Filament/Extenders/Page/HeroPageSchemaExtender.php
git add packages/mosaic/src/Filament/Resources/Contents/Schemas/Types/HeroContentSchema.php
git add packages/mosaic/src/Filament/Resources/Widgets/Schemas/Types/HeroWidgetSchema.php
git commit -m "feat: move hero filament components into mosaic package"
```

---

### Task 5: Copy View Component and Console Commands

**Files:**
- Create: `packages/mosaic/src/View/Components/Widget/Hero.php`
- Create: `packages/mosaic/src/Console/Commands/Hero/SetupCommand.php`
- Create: `packages/mosaic/src/Console/Commands/Hero/DemoCommand.php`

- [ ] **Step 1: Copy View Component Hero.php**

Read `packages/hero/src/View/Components/Widget/Hero.php`, create in `packages/mosaic/src/View/Components/Widget/Hero.php` with namespace updated to `Capell\Mosaic\View\Components\Widget`, update internal references.

- [ ] **Step 2: Copy hero setup command**

Read `packages/hero/src/Console/Commands/SetupCommand.php`, create in `packages/mosaic/src/Console/Commands/Hero/SetupCommand.php` with namespace `Capell\Mosaic\Console\Commands\Hero`, update internal references.

- [ ] **Step 3: Copy hero demo command**

Read `packages/hero/src/Console/Commands/DemoCommand.php`, create in `packages/mosaic/src/Console/Commands/Hero/DemoCommand.php` with namespace `Capell\Mosaic\Console\Commands\Hero`, update internal references.

- [ ] **Step 4: Verify no Capell\Hero references remain**

```bash
grep -r "Capell\\Hero" packages/mosaic/src/View/Components/Widget/Hero.php
grep -r "Capell\\Hero" packages/mosaic/src/Console/Commands/Hero/
```

Expected: No matches.

- [ ] **Step 5: Commit**

```bash
git add packages/mosaic/src/View/Components/Widget/Hero.php
git add packages/mosaic/src/Console/Commands/Hero/
git commit -m "feat: move hero view components and commands into mosaic package"
```

---

### Task 6: Copy hero blade templates and language files

**Files:**
- Create: `packages/mosaic/resources/views/components/hero/*` (all blade files)
- Create: `packages/mosaic/resources/views/components/widget/hero.blade.php`
- Modify: `packages/mosaic/resources/lang/en/*.php` (merge hero language keys)

- [ ] **Step 1: Copy hero blade templates**

```bash
cp -r packages/hero/resources/views/components/hero packages/mosaic/resources/views/components/
cp packages/hero/resources/views/components/widget/hero.blade.php packages/mosaic/resources/views/components/widget/
cp packages/hero/resources/views/components/pagination/summary.blade.php packages/mosaic/resources/views/components/pagination/hero-summary.blade.php
```

- [ ] **Step 2: Update any component references in hero blade files**

Search for `Capell\Hero` in the blade files and update to `Capell\Mosaic`:

```bash
grep -r "Capell\\Hero" packages/mosaic/resources/views/components/hero/
```

Expected: No matches.

- [ ] **Step 3: Copy hero language files**

```bash
cat packages/hero/resources/lang/en/form.php >> packages/mosaic/resources/lang/en/form.php
cat packages/hero/resources/lang/en/generic.php >> packages/mosaic/resources/lang/en/generic.php
```

Then manually review and clean up duplicates in the mosaic language files.

- [ ] **Step 4: Verify all copied files exist**

```bash
ls -la packages/mosaic/resources/views/components/hero/
ls packages/mosaic/resources/views/components/widget/hero.blade.php
```

- [ ] **Step 5: Commit**

```bash
git add packages/mosaic/resources/views/components/hero/
git add packages/mosaic/resources/views/components/widget/hero.blade.php
git add packages/mosaic/resources/views/components/pagination/hero-summary.blade.php
git add packages/mosaic/resources/lang/en/
git commit -m "feat: move hero blade templates and language files into mosaic package"
```

---

### Task 7: Copy hero migrations and factories

**Files:**
- Move: `packages/hero/database/migrations/*` → `packages/mosaic/database/migrations/`
- Move: `packages/hero/database/factories/*` → `packages/mosaic/database/factories/`

- [ ] **Step 1: Copy hero migrations**

```bash
cp packages/hero/database/migrations/* packages/mosaic/database/migrations/
```

- [ ] **Step 2: Copy hero factories**

```bash
cp packages/hero/database/factories/* packages/mosaic/database/factories/
```

- [ ] **Step 3: Update composer.json autoload if needed**

Check if mosaic's `composer.json` already includes database factories in autoload. If not, verify it includes:

```json
"psr-4": {
  "Capell\\Mosaic\\Database\\Factories\\": "database/factories"
}
```

- [ ] **Step 4: Commit**

```bash
git add packages/mosaic/database/migrations/
git add packages/mosaic/database/factories/
git commit -m "feat: move hero migrations and factories into mosaic package"
```

---

### Task 8: Merge hero tests into mosaic tests

**Files:**
- Move: `packages/hero/tests/*` → `packages/mosaic/tests/Hero/*`

- [ ] **Step 1: Create hero tests directory in mosaic**

```bash
mkdir -p packages/mosaic/tests/Hero
```

- [ ] **Step 2: Copy hero tests**

```bash
cp -r packages/hero/tests/* packages/mosaic/tests/Hero/
```

- [ ] **Step 3: Update namespaces in all test files**

Search and replace `Capell\Hero` with `Capell\Mosaic` in all test files:

```bash
find packages/mosaic/tests/Hero -name "*.php" -exec sed -i 's/Capell\\Hero/Capell\\Mosaic/g' {} \;
```

- [ ] **Step 4: Verify test namespace updates**

```bash
grep -r "namespace Capell" packages/mosaic/tests/Hero/ | head -5
```

Expected: All namespaces should show `Capell\Mosaic`.

- [ ] **Step 5: Run tests to verify they work**

```bash
composer test -- packages/mosaic/tests/Hero --testdox
```

Expected: Tests should pass or fail gracefully with clear error messages, not namespace errors.

- [ ] **Step 6: Commit**

```bash
git add packages/mosaic/tests/Hero/
git commit -m "feat: move hero tests into mosaic package with updated namespaces"
```

---

### Task 9: Merge MosaicServiceProvider to include hero service registration

**Files:**
- Modify: `packages/mosaic/src/Providers/MosaicServiceProvider.php`
- Delete: `packages/hero/src/Providers/HeroServiceProvider.php`

- [ ] **Step 1: Read HeroServiceProvider**

```bash
cat packages/hero/src/Providers/HeroServiceProvider.php
```

- [ ] **Step 2: Read current MosaicServiceProvider**

```bash
cat packages/mosaic/src/Providers/MosaicServiceProvider.php
```

- [ ] **Step 3: Identify service registrations from HeroServiceProvider**

Look for `register()`, `boot()`, view component registrations, command registrations, etc.

- [ ] **Step 4: Add hero registrations to MosaicServiceProvider**

Merge hero's `register()` and `boot()` logic into `MosaicServiceProvider`. For example, if hero has:

```php
public function register(): void
{
    $this->registerActions();
    $this->registerSchemas();
}

public function boot(): void
{
    $this->publishAssets();
    $this->registerViewComponents();
    $this->registerCommands();
}
```

Add these method calls and implementations to the mosaic service provider's existing methods.

- [ ] **Step 5: Update composer.json laravel.providers**

In `packages/mosaic/composer.json`, ensure only `MosaicServiceProvider` is listed (hero's provider should be removed later):

```json
"extra": {
    "laravel": {
        "providers": [
            "Capell\\Mosaic\\Providers\\MosaicServiceProvider"
        ]
    }
}
```

- [ ] **Step 6: Run tests to verify service provider works**

```bash
composer test packages/mosaic
```

Expected: Tests should pass without provider-related errors.

- [ ] **Step 7: Commit**

```bash
git add packages/mosaic/src/Providers/MosaicServiceProvider.php
git add packages/mosaic/composer.json
git commit -m "feat: merge hero service provider into mosaic service provider"
```

---

### Task 10: Update all internal references from Capell\Hero to Capell\Mosaic

**Files:**
- Modify: All files in `packages/mosaic/` that reference hero

- [ ] **Step 1: Search for all Capell\Hero references in mosaic**

```bash
grep -r "Capell\\Hero" packages/mosaic/
```

Expected: Should find references in configs, service provider bindings, or other files.

- [ ] **Step 2: Search for all Capell\Hero references in other packages**

```bash
grep -r "Capell\\Hero" packages/ --exclude-dir=hero
```

Expected: Find any references in blog, address, assistant, or other packages that depend on hero.

- [ ] **Step 3: Update references in mosaic package**

For each file found, replace `Capell\Hero` with `Capell\Mosaic`:

```bash
find packages/mosaic -name "*.php" -exec sed -i 's/Capell\\Hero/Capell\\Mosaic/g' {} \;
find packages/mosaic -name "*.json" -exec sed -i 's/Capell\\\\Hero/Capell\\\\Mosaic/g' {} \;
```

- [ ] **Step 4: Update references in other packages**

For each package that imports hero (if any), update to use mosaic instead. Search for `use Capell\Hero` or `Capell\\Hero` and update to `Capell\Mosaic`.

- [ ] **Step 5: Verify no remaining Capell\Hero references**

```bash
grep -r "Capell\\Hero" packages/ --exclude-dir=hero
```

Expected: No matches (except in the hero package directory which we'll delete).

- [ ] **Step 6: Commit**

```bash
git add packages/
git commit -m "feat: update all references from Capell\\Hero to Capell\\Mosaic"
```

---

### Task 11: Update root composer.json and remove hero package

**Files:**
- Modify: `composer.json` (root)
- Modify: `composer.local.json` (if it exists)
- Delete: `packages/hero/` directory

- [ ] **Step 1: Check if hero is listed in root composer.json**

```bash
grep -A 10 '"repositories"' composer.json | grep -i hero
```

- [ ] **Step 2: Remove hero path repository if it exists**

Edit `composer.json` and remove any path repository entry for hero.

- [ ] **Step 3: Check composer.local.json**

```bash
cat composer.local.json 2>/dev/null | grep -i hero
```

If it exists and mentions hero, remove the entry.

- [ ] **Step 4: Update root composer.json description if needed**

Update the root package description if it mentions hero separately.

- [ ] **Step 5: Run composer update**

```bash
composer update
```

Expected: Composer should recognize mosaic now includes hero functionality, no dependency errors.

- [ ] **Step 6: Delete hero directory**

```bash
rm -rf packages/hero
```

- [ ] **Step 7: Commit**

```bash
git add composer.json composer.local.json
git add -A
git commit -m "feat: remove hero package after merging into mosaic"
```

---

### Task 12: Update mosaic package metadata

**Files:**
- Modify: `packages/mosaic/README.md`
- Modify: `packages/mosaic/composer.json`

- [ ] **Step 1: Update mosaic README**

Add a section mentioning hero functionality is now part of mosaic:

```markdown
## Hero Support

This package includes the hero widget and hero content type functionality previously provided by the separate hero package.
```

- [ ] **Step 2: Update mosaic composer.json description**

Change from:

```json
"description": "Mosaic for Capell"
```

To:

```json
"description": "Mosaic layout builder and hero widgets for Capell"
```

- [ ] **Step 3: Update keywords in composer.json**

Add "hero" to the keywords array:

```json
"keywords": [
    "capell",
    "mosaic",
    "layout",
    "hero",
    "laravel",
    "filamentphp",
    "cms"
]
```

- [ ] **Step 4: Commit**

```bash
git add packages/mosaic/README.md
git add packages/mosaic/composer.json
git commit -m "docs: update mosaic package metadata to reflect hero integration"
```

---

### Task 13: Run full test suite and verify

**Files:**
- None (verification only)

- [ ] **Step 1: Run all mosaic tests**

```bash
composer test -- packages/mosaic --testdox
```

Expected: All tests should pass.

- [ ] **Step 2: Run preflight checks**

```bash
composer preflight
```

Expected: All checks should pass (Prettier, ESLint, Rector, Pint, PHPStan).

- [ ] **Step 3: Run demo workbench**

```bash
composer serve &
```

Then manually test hero functionality in the browser at http://localhost:8000.

Expected: Hero widgets and forms should work without errors.

- [ ] **Step 4: Stop demo server**

```bash
pkill -f "composer serve"
```

- [ ] **Step 5: Final git status check**

```bash
git status
```

Expected: Clean working tree, no untracked files.

---

## Summary

This plan consolidates the hero package into mosaic through systematic migration of all code, resources, and tests with namespace updates. The result is a single, cohesive mosaic package that contains both layout builder and hero functionality. All dependencies are removed, and the hero directory is deleted from the repository.

**Key points:**
- Frequent commits after each logical group of changes
- All namespaces updated from `Capell\Hero` to `Capell\Mosaic`
- Tests moved and namespace-updated before deletion of hero package
- Service provider merged into MosaicServiceProvider
- All references updated across the codebase
