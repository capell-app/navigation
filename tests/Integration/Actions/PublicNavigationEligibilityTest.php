<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Contracts\RenderedModelTracker;
use Capell\Frontend\Enums\CacheEnum;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Loader\NavigationLoader;
use Dom\HTMLDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

/**
 * @param  list<Page>  $pages
 * @return list<array<string, mixed>>
 */
function publicEligibilityItems(array $pages): array
{
    return array_map(static fn (Page $page): array => [
        'type' => NavigationItemType::Page->value,
        'label' => null,
        'data' => ['pageable_id' => $page->getKey(), 'pageable_type' => $page->getMorphClass()],
    ], $pages);
}

/** @return list<string> */
function anonymousMenuHrefs(Site $site, Language $language, Page $currentPage): array
{
    auth()->logout();
    $domain = $site->siteDomains()->firstOrFail();
    $view = test()->blade(
        '<x-capell-navigation::menu key="eligibility-menu" :site="$site" :language="$language" :page="$currentPage" :domain="$domain" />',
        ['site' => $site, 'language' => $language, 'currentPage' => $currentPage, 'domain' => $domain],
    );
    $html = $view instanceof Stringable ? (string) $view : '';
    $dom = HTMLDocument::createFromString($html, LIBXML_NOERROR, 'UTF-8');
    $hrefs = [];

    foreach ($dom->querySelectorAll('nav a') as $anchor) {
        $href = $anchor->getAttribute('href');

        if (is_string($href)) {
            $hrefs[] = $href;
        }
    }

    return $hrefs;
}

beforeEach(function (): void {
    $this->language = Language::factory()->default()->create();
    $this->site = Site::factory()->language($this->language)->withTranslations(siteDomainData: [
        'scheme' => 'https', 'domain' => 'eligibility.example.test', 'path' => null,
    ])->create();
    $this->home = Page::factory()->site($this->site)->home()->withTranslations($this->language, slug: '/')->create();
});

it('drops a menu item when its target page is scheduled after the menu was first rendered', function (): void {
    $target = Page::factory()->site($this->site)->withTranslations($this->language, slug: '/soon')->create();
    Navigation::factory()->site($this->site)->language($this->language)->create([
        'key' => 'eligibility-menu',
        'items' => publicEligibilityItems([$target]),
    ]);

    expect(anonymousMenuHrefs($this->site, $this->language, $this->home))->toHaveCount(1);

    $target->update(['visible_from' => now()->addDay()]);

    expect(anonymousMenuHrefs($this->site, $this->language, $this->home))->toBe([]);
});

it('shows a scheduled page item once it becomes live', function (): void {
    $target = Page::factory()->site($this->site)->withTranslations($this->language, slug: '/later')->create([
        'visible_from' => now()->addDay(),
    ]);
    Navigation::factory()->site($this->site)->language($this->language)->create([
        'key' => 'eligibility-menu',
        'items' => publicEligibilityItems([$target]),
    ]);

    expect(anonymousMenuHrefs($this->site, $this->language, $this->home))->toBe([]);

    $target->update(['visible_from' => now()->subMinute()]);

    expect(anonymousMenuHrefs($this->site, $this->language, $this->home))->toHaveCount(1);
});

it('does not render an item pointing at an ineligible current page', function (): void {
    $scheduled = Page::factory()->site($this->site)->withTranslations($this->language, slug: '/scheduled')->create([
        'visible_from' => now()->addDay(),
    ]);
    $ready = Page::factory()->site($this->site)->withTranslations($this->language, slug: '/ready')->create();
    Navigation::factory()->site($this->site)->language($this->language)->create([
        'key' => 'eligibility-menu',
        'items' => publicEligibilityItems([$ready, $scheduled]),
    ]);

    $hrefs = anonymousMenuHrefs($this->site, $this->language, $scheduled);

    expect($hrefs)->toHaveCount(1)
        ->and($hrefs[0])->toEndWith('/ready');
});

it('renders exactly the eligible items', function (): void {
    $ready = Page::factory()->site($this->site)->withTranslations($this->language, slug: '/ready')->create();
    $scheduled = Page::factory()->site($this->site)->withTranslations($this->language, slug: '/scheduled')->create(['visible_from' => now()->addDay()]);
    $disabled = Page::factory()->site($this->site)->withTranslations($this->language, slug: '/disabled')->create();
    $disabled->pageUrls()->update(['status' => false]);
    $items = [
        ...publicEligibilityItems([$ready, $scheduled, $disabled]),
        ['label' => 'Hidden item', 'type' => 'link', 'is_visible' => false, 'data' => ['url' => '/hidden']],
    ];
    Navigation::factory()->site($this->site)->language($this->language)->create(['key' => 'eligibility-menu', 'items' => $items]);
    BuildNavigationRenderModelAction::flushPageCache();

    $hrefs = anonymousMenuHrefs($this->site, $this->language, $this->home);

    expect($hrefs)->toHaveCount(1)->and($hrefs[0])->toEndWith('/ready');
});

it('checks the current page is published once however many menus link to it', function (): void {
    $about = Page::factory()->site($this->site)->withTranslations($this->language, slug: '/about')->create();

    foreach (['eligibility-menu', 'eligibility-menu-second', 'eligibility-menu-third'] as $key) {
        Navigation::factory()->site($this->site)->language($this->language)->create([
            'key' => $key,
            'items' => publicEligibilityItems([$this->home, $about]),
        ]);
    }

    auth()->logout();
    // A public request resolves the site with its menus and their blueprints already loaded.
    $site = $this->site->load('navigations.blueprint');
    $language = $this->language;
    $currentPage = $this->home;
    $domain = $site->siteDomains()->firstOrFail();
    $publishedChecks = 0;
    DB::listen(function (QueryExecuted $query) use (&$publishedChecks): void {
        if (preg_match('/^select exists\(select \* from [`"]pages[`"] where [`"]pages[`"]\.[`"]id[`"] = \?/', $query->sql) === 1) {
            $publishedChecks++;
        }
    });

    $html = '';
    foreach (['eligibility-menu', 'eligibility-menu-second', 'eligibility-menu-third'] as $key) {
        $view = test()->blade(
            '<x-capell-navigation::menu :key="$key" :site="$site" :language="$language" :page="$currentPage" :domain="$domain" />',
            ['key' => $key, 'site' => $site, 'language' => $language, 'currentPage' => $currentPage, 'domain' => $domain],
        );
        $html .= $view instanceof Stringable ? (string) $view : '';
    }

    expect(substr_count($html, '/about"'))->toBe(3)
        ->and($publishedChecks)->toBe(1);
});

it('hands the render tracker hydrated menus from cold, legacy and warm fallback caches', function (bool $legacyCache): void {
    $about = Page::factory()->site($this->site)->withTranslations($this->language, slug: '/about')->create();
    $menus = [];

    foreach (['eligibility-menu', 'eligibility-menu-second'] as $key) {
        $menus[] = Navigation::factory()->site($this->site)->language($this->language)->create([
            'key' => $key,
            'items' => publicEligibilityItems([$this->home, $about]),
        ]);
    }

    auth()->logout();
    // The loader falls back to its own query when the site arrives without `navigations` loaded.
    $site = Site::query()->whereKey($this->site->getKey())->firstOrFail();
    $language = $this->language;
    $currentPage = $this->home;
    $domain = $site->siteDomains()->firstOrFail();
    expect($site->relationLoaded('navigations'))->toBeFalse();

    config(['capell.disable_cache' => false]);
    $cacheKey = CacheEnum::siteNavigations($site->id);
    CapellCore::removeCacheKey($cacheKey);

    if ($legacyCache) {
        // Collections cached by earlier releases have no blueprint relation.
        $cachedMenus = Navigation::query()->where('site_id', $site->getKey())->get();
        expect($cachedMenus->every(fn (Navigation $menu): bool => ! $menu->relationLoaded('blueprint')))->toBeTrue();
        CapellCore::setToCache($cacheKey, $cachedMenus);
        CapellCore::flushLocalCache();
    }

    $tracker = new class implements RenderedModelTracker
    {
        /** @var list<array{class-string<Model>, int, bool}> */
        public array $tracked = [];

        #[Override]
        public function track(Model $model): void
        {
            if ($model instanceof Navigation) {
                $this->tracked[] = [$model::class, $model->id, $model->relationLoaded('blueprint')];
            }
        }

        #[Override]
        public function trackByClass(Model $model, string $modelClass): void {}

        #[Override]
        public function tracked(string $modelType): int
        {
            return 0;
        }
    };
    app()->instance(RenderedModelTracker::class, $tracker);

    $blueprintQueries = 0;
    DB::listen(function (QueryExecuted $query) use (&$blueprintQueries): void {
        if (preg_match('/from [`"]blueprints[`"]/', $query->sql) !== 1) {
            return;
        }

        $fromLoader = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))
            ->contains(fn (array $frame): bool => ($frame['class'] ?? null) === NavigationLoader::class);

        if ($fromLoader) {
            $blueprintQueries++;
        }
    });

    $wasPreventingLazyLoading = Model::preventsLazyLoading();
    Model::preventLazyLoading();
    $html = '';

    try {
        foreach (['eligibility-menu', 'eligibility-menu-second'] as $key) {
            $view = test()->blade(
                '<x-capell-navigation::menu :key="$key" :site="$site" :language="$language" :page="$currentPage" :domain="$domain" />',
                ['key' => $key, 'site' => $site, 'language' => $language, 'currentPage' => $currentPage, 'domain' => $domain],
            );
            $html .= $view instanceof Stringable ? (string) $view : '';
        }
    } finally {
        Model::preventLazyLoading($wasPreventingLazyLoading);
    }

    expect(substr_count($html, '/about"'))->toBe(2)
        ->and($tracker->tracked)->toContain([Navigation::class, $menus[0]->id, true])
        ->and($tracker->tracked)->toContain([Navigation::class, $menus[1]->id, true])
        ->and($blueprintQueries)->toBe(1);
})->with(['cold cache' => false, 'legacy cache' => true]);
