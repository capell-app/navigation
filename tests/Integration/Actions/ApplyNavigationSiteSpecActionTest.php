<?php

declare(strict_types=1);

use Capell\Core\Contracts\SiteSpec\SiteSpecApplier;
use Capell\Core\Data\SiteSpec\CapellSiteSpecData;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\SiteSpec\SiteSpecApplierRegistry;
use Capell\Navigation\Actions\ApplyNavigationSiteSpecAction;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;

it('registers the navigation SiteSpec applier through the stable core tag', function (): void {
    expect(resolve(SiteSpecApplierRegistry::class)->keys())->toContain('navigation')
        ->and(app()->tagged(SiteSpecApplier::TAG))
        ->toContainEqual(resolve(ApplyNavigationSiteSpecAction::class));
});

it('applies ordered page references and replaces the same site navigation idempotently', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    $site = Site::factory()->language($language)->withTranslations()->create();
    $home = Page::factory()->site($site)->withTranslations(collect([$language]))->create(['name' => 'Home']);
    $about = Page::factory()->site($site)->withTranslations(collect([$language]))->create(['name' => 'About']);
    $spec = CapellSiteSpecData::from([
        'site' => ['name' => 'Harbour Books'],
        'theme' => ['key' => 'default'],
        'pages' => [],
        'navigations' => [[
            'key' => 'main',
            'name' => 'Primary navigation',
            'pageSlugs' => ['about', 'home'],
        ]],
    ]);

    $pagesBySlug = ['home' => $home, 'about' => $about];

    ApplyNavigationSiteSpecAction::run($spec, $site, $pagesBySlug);
    ApplyNavigationSiteSpecAction::run($spec, $site, $pagesBySlug);

    $navigation = Navigation::query()
        ->whereBelongsTo($site)
        ->whereBelongsTo($language)
        ->where('key', 'main')
        ->sole();
    $items = collect($navigation->items)->values();

    expect(Navigation::query()->whereBelongsTo($site)->where('key', 'main')->count())->toBe(1)
        ->and($navigation->name)->toBe('Primary navigation')
        ->and($items)->toHaveCount(2)
        ->and($items->pluck('type')->all())->toBe([
            NavigationItemType::Page->value,
            NavigationItemType::Page->value,
        ])
        ->and($items->pluck('data.pageable_id')->all())->toBe([$about->getKey(), $home->getKey()])
        ->and($items->pluck('data.pageable_type')->all())->toBe([$about->getMorphClass(), $home->getMorphClass()]);
});
