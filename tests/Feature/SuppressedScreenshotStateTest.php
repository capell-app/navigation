<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Sites\Pages\EditSite;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
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
    $page = resolve(SeedNavigationScreenshotFixtureAction::class)->handle();
    resolve(SeedNavigationScreenshotFixtureAction::class)->handle();
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

it('opens only the selected site prepared landing editor without changing content', function (bool $hasSelectedLanding, bool $otherLanguage): void {
    $this->actingAsAdmin(['email' => 'admin@example.com']);
    $site = Site::factory()->withTranslations()->create();
    $otherSite = Site::factory()->withTranslations()->create();
    $blueprint = Blueprint::factory()->page()->create(['admin' => ['type_configurator' => 'page', 'configurator' => 'Landing']]);
    $landingSite = $hasSelectedLanding || $otherLanguage ? $site : $otherSite;
    $landing = Page::factory()->site($landingSite)->withTranslations()->create([
        'blueprint_id' => $blueprint->id,
        'meta' => ['theme_demo' => ['surface' => 'homepage']],
    ]);
    $lookalike = Page::factory()->site($site)->withTranslations()->create([
        'meta' => ['theme_demo' => ['surface' => 'homepage']],
    ]);
    PageUrl::factory()->site($landingSite)->page($landing)->create([
        'url' => '/',
        'language_id' => $otherLanguage ? Language::factory()->create()->id : $landingSite->language_id,
    ]);
    $preview = Page::factory()->site($site)->withTranslations()->create([
        'blueprint_id' => $blueprint->id,
        'meta' => ['theme_demo' => ['surface' => 'homepage']],
    ]);
    PageUrl::factory()->site($site)->page($preview)->create(['url' => '/theme-default', 'language_id' => $site->language_id]);
    $beforeUrls = PageUrl::query()->orderBy('id')->get()->map->getRawOriginal()->all();
    $beforePreview = $preview->refresh()->getRawOriginal();
    $beforeLanding = $landing->refresh()->getRawOriginal();
    $beforeLookalike = $lookalike->refresh()->getRawOriginal();
    $pages = Page::query()->count();
    $navigations = Navigation::query()->count();
    $priorSite = getenv('CAPELL_SCREENSHOT_SITE_ID');
    putenv('CAPELL_SCREENSHOT_SITE_ID=' . $site->id);

    try {
        require __DIR__ . '/../../workbench/routes/screenshot-fixtures.php';
        $response = $this->get('/screenshot-fixtures/navigation/landing-page');
        if ($hasSelectedLanding) {
            $key = $landing->getRouteKey();
            throw_unless(is_int($key) || is_string($key), RuntimeException::class, 'The landing page route key must be scalar.');
            $response->assertRedirect('/admin/pages/' . $key . '/edit');
        } else {
            $response->assertNotFound();
        }
    } finally {
        putenv($priorSite === false ? 'CAPELL_SCREENSHOT_SITE_ID' : 'CAPELL_SCREENSHOT_SITE_ID=' . $priorSite);
    }

    expect($landing->fresh()?->getRawOriginal())->toBe($beforeLanding)
        ->and($lookalike->fresh()?->getRawOriginal())->toBe($beforeLookalike)
        ->and($preview->fresh()?->getRawOriginal())->toBe($beforePreview)
        ->and(PageUrl::query()->orderBy('id')->get()->map->getRawOriginal()->all())->toBe($beforeUrls)
        ->and(Page::query()->count())->toBe($pages)
        ->and(Navigation::query()->count())->toBe($navigations);
})->with([
    'selected landing' => [true, false],
    'only foreign landing' => [false, false],
    'only other-language root' => [false, true],
]);
