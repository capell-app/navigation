<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Sites\Pages\EditSite;
use Capell\Core\Models\Site;
use Capell\Navigation\Actions\SeedNavigationScreenshotFixtureAction;
use Capell\Navigation\Filament\Resources\Sites\RelationManagers\NavigationsRelationManager;
use Capell\Navigation\Models\Navigation;
use Capell\Tests\Support\CapellManifest;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Tests\Support\ScreenshotManifest;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    $this->actingAsAdmin();
    putenv('CAPELL_SCREENSHOT_FIXTURE=record-state');
    putenv('CAPELL_SCREENSHOT_APP_PATH=' . base_path());
});

afterEach(function (): void {
    putenv('CAPELL_SCREENSHOT_FIXTURE');
    putenv('CAPELL_SCREENSHOT_APP_PATH');
});

it('opens the seeded page navigation tab and populated site relation manager', function (): void {
    $site = Site::factory()->withTranslations()->create();
    require __DIR__ . '/../../workbench/routes/screenshot-fixtures.php';
    $page = app(SeedNavigationScreenshotFixtureAction::class)->handle();
    app(SeedNavigationScreenshotFixtureAction::class)->handle();
    expect(Navigation::query()->where('key', 'screenshot-main-menu')->count())->toBe(1);
    $response = $this->get(navigationSuppressedCaptureUrl('page-form-navigation-tab'));
    $pageRouteKey = $page->getRouteKey();
    throw_unless(is_int($pageRouteKey) || is_string($pageRouteKey), RuntimeException::class, 'The Navigation screenshot page route key must be scalar.');
    $response->assertRedirect('/admin/pages/' . $pageRouteKey . '/edit');
    $location = $response->baseResponse->headers->get('Location');
    throw_unless(is_string($location), RuntimeException::class, 'The Navigation screenshot response did not redirect to a location.');
    $this->get($location)->assertOk()->assertSee('Screenshot main menu');
    $this->get(navigationSuppressedCaptureUrl('site-relation-manager-for-navigations'))->assertRedirect('/admin/sites/' . $site->getRouteKey() . '/edit');
    Livewire::test(NavigationsRelationManager::class, [
        'ownerRecord' => $site, 'pageClass' => EditSite::class,
    ])->assertOk()->assertSee('Screenshot main menu');
});

it('declares the screenshot seed command in both manifests', function (): void {
    $manifestPath = __DIR__ . '/../../capell.json';
    $screenshotsPath = __DIR__ . '/../../docs/screenshots.json';
    $command = 'capell:navigation:screenshot-fixture';
    expect(CapellManifest::screenshotFixtureCommand($manifestPath))->toBe($command)
        ->and(ScreenshotManifest::fixtureCommands($screenshotsPath))->toContain($command)
        ->and(CapellManifest::consoleCommandNames($manifestPath))->toContain($command)
        ->and(Artisan::all())->toHaveKey($command);
    $this->artisan($command)->assertFailed();
});

function navigationSuppressedCaptureUrl(string $key): string
{
    return ScreenshotManifest::captureUrl(__DIR__ . '/../../docs/screenshots.json', $key);
}
