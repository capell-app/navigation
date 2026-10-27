<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Models\Navigation;
use Capell\ScreenshotFixtures\FixtureState;
use RuntimeException;

final class SeedNavigationScreenshotFixtureAction
{
    public function handle(bool $landingPage = false): Page
    {
        $path = getenv('CAPELL_SCREENSHOT_APP_PATH');
        throw_unless(
            app()->environment(['local', 'testing'])
                && in_array(getenv('CAPELL_SCREENSHOT_FIXTURE'), ['1', 'true', 'record-state'], true)
                && is_string($path) && realpath($path) === realpath(base_path())
                && (realpath(base_path()) === realpath(dirname(__DIR__, 4) . '/vendor/orchestra/testbench-core/laravel')
                    || str_starts_with((string) realpath(base_path()), dirname(__DIR__, 4) . '/tmp/testbench-skeletons/')),
            RuntimeException::class,
            'Screenshot fixtures require the explicit disposable local screenshot environment.',
        );

        require_once dirname(__DIR__, 4) . '/scripts/screenshots/FixtureState.php';
        FixtureState::boot();

        if ($landingPage) {
            $site = FixtureState::selectedSite();

            return Page::query()
                ->where('site_id', $site->id)
                ->where('meta->theme_demo->surface', 'homepage')
                ->whereRelation('blueprint', 'admin->configurator', 'Landing')
                ->whereRelation('pageUrl', ['url' => '/', 'language_id' => $site->language_id])
                ->sole();
        }

        $site = Site::query()->firstOrFail();
        $page = Page::query()->where('site_id', $site->id)->where('name', 'Navigation screenshot guide')->first();
        $page ??= Page::factory()->site($site)->withTranslations(data: ['title' => 'Navigation screenshot guide'])->create(['name' => 'Navigation screenshot guide']);
        $navigation = Navigation::query()->where('site_id', $site->id)->where('key', 'screenshot-main-menu')->first();
        $navigation ??= Navigation::factory()->site($site)->create([
            'key' => 'screenshot-main-menu',
            'name' => 'Screenshot main menu',
            'language_id' => $site->language_id,
        ]);
        $navigation->items = [['type' => 'page', 'data' => ['pageable_id' => $page->id, 'pageable_type' => $page->getMorphClass()], 'children' => []]];
        $navigation->save();

        return $page;
    }
}
