<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Models\Navigation;
use RuntimeException;

final class SeedNavigationScreenshotFixtureAction
{
    public function handle(): Page
    {
        $path = getenv('CAPELL_SCREENSHOT_APP_PATH');
        throw_unless(
            app()->environment(['local', 'testing'])
                && in_array(getenv('CAPELL_SCREENSHOT_FIXTURE'), ['1', 'true', 'record-state'], true)
                && is_string($path) && realpath($path) === realpath(base_path()),
            RuntimeException::class,
            'Screenshot fixtures require the explicit disposable local screenshot environment.',
        );

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
