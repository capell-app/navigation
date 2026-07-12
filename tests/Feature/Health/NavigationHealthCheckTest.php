<?php

declare(strict_types=1);

use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Health\NavigationHealthCheck;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\RenderHooks\RegisterFoundationHeaderNavigationHook;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('reports a compatible capell api version', function (): void {
    expect(NavigationHealthCheck::compatibleCapellApiVersion())->toBe('^0.0');
});

it('runs real diagnostics returning check results', function (): void {
    $results = NavigationHealthCheck::runDiagnostics();

    expect($results)->toHaveCount(5)
        ->and($results->every(static fn (mixed $result): bool => $result instanceof DoctorCheckResultData))->toBeTrue();
});

it('passes when the storage table and morph alias are present', function (): void {
    $check = new NavigationHealthCheck;

    expect($check->storageTableCheck()->passed)->toBeTrue()
        ->and($check->modelMorphAliasCheck()->passed)->toBeTrue();
});

it('passes the header render hook check when the navigation hook scenarios are registered', function (): void {
    $check = new NavigationHealthCheck;

    expect($check->hasHeaderRenderHook())->toBeTrue()
        ->and($check->headerRenderHookCheck()->passed)->toBeTrue();
});

it('fails the header render hook check when only unrelated header hooks are registered', function (): void {
    $registry = new RenderHookRegistry;

    $registry->register(
        RenderHookLocation::HeaderAfter,
        static fn (): string => '',
        scenario: 'unrelated-header-hook',
        target: RegisterFoundationHeaderNavigationHook::Target,
    );

    app()->instance(RenderHookRegistry::class, $registry);

    $check = new NavigationHealthCheck;

    expect($check->hasHeaderRenderHook())->toBeFalse()
        ->and($check->headerRenderHookCheck()->passed)->toBeFalse()
        ->and(NavigationHealthCheck::passed())->toBeFalse();
});

it('fails the header render hook check when a navigation header hook scenario is missing', function (): void {
    $registry = new RenderHookRegistry;

    $registry->register(
        RenderHookLocation::HeaderAfter,
        static fn (): string => '',
        scenario: RegisterFoundationHeaderNavigationHook::DefaultScenario,
        target: RegisterFoundationHeaderNavigationHook::Target,
    );

    app()->instance(RenderHookRegistry::class, $registry);

    $check = new NavigationHealthCheck;

    expect($check->hasHeaderRenderHook())->toBeFalse()
        ->and($check->headerRenderHookCheck()->passed)->toBeFalse()
        ->and(NavigationHealthCheck::passed())->toBeFalse();
});

it('fails the storage table check when the navigations table is missing', function (): void {
    Schema::drop('navigation_page_references');
    Schema::drop('navigations');

    $check = new NavigationHealthCheck;

    expect($check->hasStorageTable())->toBeFalse()
        ->and($check->missingStorageTables())->toContain('navigations')
        ->and($check->storageTableCheck()->passed)->toBeFalse()
        ->and(NavigationHealthCheck::passed())->toBeFalse();
});

it('fails the storage table check when the navigation page references table is missing', function (): void {
    Schema::drop('navigation_page_references');

    $check = new NavigationHealthCheck;

    expect($check->hasStorageTable())->toBeFalse()
        ->and($check->missingStorageTables())->toContain('navigation_page_references')
        ->and($check->storageTableCheck()->passed)->toBeFalse()
        ->and(NavigationHealthCheck::passed())->toBeFalse();
});

it('fails the morph alias check when the Navigation model is not registered', function (): void {
    Relation::morphMap([], merge: false);

    $check = new NavigationHealthCheck;

    expect($check->hasNavigationMorphAlias())->toBeFalse()
        ->and($check->modelMorphAliasCheck()->passed)->toBeFalse()
        ->and(NavigationHealthCheck::passed())->toBeFalse();
});

it('fails the main navigation coverage check when a site cannot resolve a main menu', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations()
        ->create();

    $check = new NavigationHealthCheck;
    $result = $check->mainNavigationCoverageCheck();

    expect($check->hasMainNavigationForEverySite())->toBeFalse()
        ->and($check->missingMainNavigationSiteIds())->toContain((int) $site->getKey())
        ->and($result->passed)->toBeFalse()
        ->and($result->label)->toBe((string) __('capell-navigation::generic.health_main_navigation_coverage_label'))
        ->and($result->message)->toBe((string) __('capell-navigation::generic.health_main_navigation_coverage_failed', ['sites' => (string) $site->getKey()]))
        ->and($result->remediation)->toBe((string) __('capell-navigation::generic.health_main_navigation_coverage_remediation'))
        ->and(NavigationHealthCheck::passed())->toBeFalse();
});

it('passes the main navigation coverage check when a global main menu exists', function (): void {
    $language = Language::factory()->default()->create();
    Site::factory()
        ->language($language)
        ->withTranslations()
        ->create();

    Navigation::factory()->create([
        'key' => NavigationHandle::Main->value,
        'site_id' => null,
        'language_id' => null,
    ]);

    $check = new NavigationHealthCheck;
    $result = $check->mainNavigationCoverageCheck();

    expect($check->hasMainNavigationForEverySite())->toBeTrue()
        ->and($check->missingMainNavigationSiteIds())->toBeEmpty()
        ->and($result->passed)->toBeTrue()
        ->and($result->label)->toBe((string) __('capell-navigation::generic.health_main_navigation_coverage_label'))
        ->and($result->message)->toBe((string) __('capell-navigation::generic.health_main_navigation_coverage_passed'))
        ->and($result->remediation)->toBeNull();
});

it('fails the page reference integrity check when references point at missing pageable records', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations()
        ->create();
    $page = Page::factory()
        ->site($site)
        ->withTranslations()
        ->create();
    $navigation = Navigation::factory()
        ->site($site)
        ->language($language)
        ->create(['key' => NavigationHandle::Main->value]);
    $now = now();

    DB::table('navigation_page_references')->insert([
        [
            'navigation_id' => $navigation->getKey(),
            'site_id' => $site->getKey(),
            'language_id' => $language->getKey(),
            'pageable_type' => $page->getMorphClass(),
            'pageable_id' => $page->getKey(),
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'navigation_id' => $navigation->getKey(),
            'site_id' => $site->getKey(),
            'language_id' => $language->getKey(),
            'pageable_type' => $page->getMorphClass(),
            'pageable_id' => 999_999,
            'created_at' => $now,
            'updated_at' => $now,
        ],
    ]);

    $check = new NavigationHealthCheck;
    $result = $check->pageReferenceIntegrityCheck();

    expect($check->hasOrphanedPageReferences())->toBeTrue()
        ->and($check->orphanedPageReferenceCount())->toBe(1)
        ->and($result->passed)->toBeFalse()
        ->and($result->label)->toBe((string) __('capell-navigation::generic.health_page_reference_integrity_label'))
        ->and($result->message)->toBe((string) __('capell-navigation::generic.health_page_reference_integrity_failed', ['count' => 1]))
        ->and($result->remediation)->toBe((string) __('capell-navigation::generic.health_page_reference_integrity_remediation'))
        ->and(NavigationHealthCheck::passed())->toBeFalse();
});
