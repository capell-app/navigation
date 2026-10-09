<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Navigation\Actions\BuildNavigationChildFragmentAction;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Data\NavigationItemRenderData;
use Capell\Navigation\Data\NavigationRenderContextData;
use Capell\Navigation\Enums\NavigationCacheEnum;
use Capell\Navigation\Enums\NavigationChildrenLoadingEnum;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\NavigationCacheKeys;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

it('returns a lazy navigation child fragment for a valid public payload', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'http', 'domain' => 'localhost', 'path' => null])
        ->create();
    $parentPage = Page::factory()->site($site)->withTranslations()->create();
    $currentPage = Page::factory()->site($site)->withTranslations()->parent($parentPage)->create();
    $siteDomain = $site->siteDomains->first();

    $navigation = Navigation::factory()->create([
        'key' => 'main',
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'items' => [
            [
                'key' => 'parent',
                'label' => 'Parent',
                'type' => NavigationItemType::Link->value,
                'data' => [
                    'url' => '/parent',
                    'children_loading' => NavigationChildrenLoadingEnum::Lazy->value,
                ],
                'children' => [
                    [
                        'key' => 'current',
                        'type' => NavigationItemType::Page->value,
                        'data' => [
                            'pageable_id' => $currentPage->getKey(),
                            'pageable_type' => $currentPage->getMorphClass(),
                        ],
                    ],
                ],
            ],
        ],
    ])->refresh();

    $renderModel = BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $language,
        siteDomain: $siteDomain,
    ));

    $url = navigationChildFragmentUrl(navigationChildFragmentFirstItem($renderModel->items)->lazyFragmentUrl);

    expect($url)->toBeString();

    $this->get($url)
        ->assertSuccessful()
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertSee($currentPage->translation->label)
        ->assertSee('is-active');

    $queryString = parse_url($url, PHP_URL_QUERY);
    expect($queryString)->toBeString();
    parse_str(is_string($queryString) ? $queryString : '', $query);
    $payload = $query['payload'] ?? null;
    expect($payload)->toBeString();
    $payload = is_string($payload) ? $payload : '';
    expect(BuildNavigationChildFragmentAction::run($payload, 'attacker.test'))->toBeNull();
    $locator = json_decode(Crypt::decryptString($payload), true, flags: JSON_THROW_ON_ERROR);
    expect($locator)->toBeArray()->toHaveKey('expires_at');

    if (! is_array($locator)) {
        throw new LogicException('Expected a decoded navigation locator.');
    }

    $locator['expires_at'] = now()->subSeconds(2)->getTimestamp();
    $expiredPayload = Crypt::encryptString(json_encode($locator, JSON_THROW_ON_ERROR));
    expect(BuildNavigationChildFragmentAction::run($expiredPayload, 'localhost'))->toBeNull();

    $navigation->name = 'Changed after locator issue';
    $navigation->updated_at = now()->addSecond();
    $navigation->save();

    $this->get($url)->assertNotFound();
});

it('can repeatedly load the same lazy mega menu fragment', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'http', 'domain' => 'localhost', 'path' => null])
        ->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $siteDomain = $site->siteDomains->first();

    Navigation::factory()->create([
        'key' => 'main',
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'items' => [
            [
                'key' => 'mega-menu',
                'label' => 'Solutions',
                'type' => NavigationItemType::Link->value,
                'data' => [
                    'url' => '/solutions',
                    'children_loading' => NavigationChildrenLoadingEnum::Lazy->value,
                ],
                'children' => [
                    [
                        'key' => 'platform',
                        'label' => 'Platform',
                        'type' => NavigationItemType::Link->value,
                        'data' => ['url' => '/solutions/platform'],
                    ],
                    [
                        'key' => 'services',
                        'label' => 'Services',
                        'type' => NavigationItemType::Link->value,
                        'data' => ['url' => '/solutions/services'],
                    ],
                ],
            ],
        ],
    ]);

    $view = $this->blade(
        '<x-capell-navigation::menu key="main" :site="$site" :language="$language" :page="$currentPage" :domain="$siteDomain" />',
        ['site' => $site, 'language' => $language, 'currentPage' => $currentPage, 'siteDomain' => $siteDomain],
    );

    preg_match('/data-navigation-fragment-url="([^"]+)"/', (string) $view, $matches);

    $url = html_entity_decode($matches[1] ?? '', ENT_QUOTES | ENT_HTML5);

    expect($url)->toStartWith('http://localhost/_capell/navigation/children?payload=');

    $firstResponse = $this->get($url)
        ->assertSuccessful()
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertSee('Platform')
        ->assertSee('Services')
        ->assertDontSee('data-navigation-lazy-fragment');

    $firstContent = $firstResponse->getContent();

    if ($firstContent === false) {
        throw new RuntimeException('Expected the response to contain HTML.');
    }

    foreach (range(1, 3) as $loadAttempt) {
        $this->get($url)
            ->assertSuccessful()
            ->assertHeader('X-Robots-Tag', 'noindex')
            ->assertSee('Platform')
            ->assertSee('Services')
            ->assertContent($firstContent);
    }
});

it('returns not found for an invalid lazy navigation fragment payload', function (): void {
    $this->get(route('capell-navigation.children', ['payload' => 'invalid']))
        ->assertNotFound();
});

it('caches separate guest child fragments for domains sharing a site and language', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'primary.test', 'path' => null])
        ->create();
    $primaryDomain = $site->siteDomains()->where('language_id', $language->getKey())->firstOrFail();
    $secondaryDomain = SiteDomain::factory()->site($site)->language($language)->create([
        'scheme' => 'https',
        'domain' => 'secondary.test',
        'path' => null,
        'port' => null,
    ]);
    $page = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $navigation = Navigation::factory()->create([
        'key' => 'main',
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'items' => [[
            'key' => 'parent',
            'label' => 'Parent',
            'type' => NavigationItemType::Link->value,
            'data' => [
                'url' => '/parent',
                'children_loading' => NavigationChildrenLoadingEnum::Lazy->value,
            ],
            'children' => [[
                'key' => 'home',
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
            ]],
        ]],
    ])->refresh();
    $urls = [];
    $contents = [];
    $writtenKeys = [];
    Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$writtenKeys): void {
        if (str_starts_with($event->key, NavigationCacheEnum::LazyFragments->value . '-')) {
            $writtenKeys[$event->key] = true;
        }
    });

    foreach ([$primaryDomain, $secondaryDomain] as $domain) {
        $model = BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
            navigation: $navigation,
            page: $page,
            site: $site,
            language: $language,
            siteDomain: $domain,
        ));
        $fragmentUrl = navigationChildFragmentUrl(navigationChildFragmentFirstItem($model->items)->lazyFragmentUrl);
        $queryString = parse_url($fragmentUrl, PHP_URL_QUERY);
        throw_unless(is_string($queryString), RuntimeException::class, 'Expected a locator query string.');
        $url = 'https://' . $domain->domain . '/_capell/navigation/children?' . $queryString;
        $response = $this->get($url)->assertSuccessful();
        $content = $response->getContent();
        throw_unless(is_string($content), RuntimeException::class, 'Expected a guest fragment response body.');
        $urls[] = $url;
        $contents[] = $content;
    }

    expect(array_keys($writtenKeys))->toHaveCount(2);

    foreach ($urls as $index => $url) {
        $this->get($url)->assertSuccessful()->assertContent($contents[$index]);
    }
});

it('applies the named child fragment limiter with a higher shared visitor allowance', function (): void {
    expect(Route::getRoutes()->getByName('capell-navigation.children')?->gatherMiddleware())
        ->toContain('throttle:capell-navigation-children')
        ->and(config('capell-navigation.children.rate_limit_per_minute'))->toBe(300);

    $this->freezeTime();
    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.61']);

    for ($attempt = 0; $attempt < 61; $attempt++) {
        $this->get(route('capell-navigation.children', ['payload' => 'invalid-' . $attempt]))
            ->assertNotFound();
    }
});

it('uses the configured child fragment allowance even when the invalid payload changes', function (): void {
    config(['capell-navigation.children.rate_limit_per_minute' => 2]);
    $this->freezeTime();
    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.60']);

    for ($attempt = 0; $attempt < 2; $attempt++) {
        $this->get(route('capell-navigation.children', ['payload' => 'invalid-' . $attempt]))
            ->assertNotFound();
    }

    $this->get(route('capell-navigation.children', ['payload' => 'invalid-2']))
        ->assertTooManyRequests()
        ->assertHeader('X-RateLimit-Limit', '2')
        ->assertHeader('Retry-After');

    $this->travel(61)->seconds();

    $this->get(route('capell-navigation.children', ['payload' => 'invalid-after-window']))
        ->assertNotFound();
});

it('rejects a child fragment replayed on a different scheme or port of the same host', function (string $scheme, ?int $port): void {
    $url = navigationChildOriginFragmentUrl($scheme, $port);

    $this->get($url)->assertNotFound();
})->with([
    'different scheme' => ['https', null],
    'different port' => ['http', 8080],
]);

it('accepts a child fragment for a path-mounted domain because the route is always served from the origin root', function (): void {
    $queryString = parse_url(navigationChildOriginFragmentUrl('http', null, '/mounted-site/'), PHP_URL_QUERY);
    throw_unless(is_string($queryString), RuntimeException::class, 'Expected a locator query string.');

    $this->get('http://localhost/_capell/navigation/children?' . $queryString)
        ->assertSuccessful()
        ->assertSee('Public content');
});

it('accepts a child fragment on the configured non-default scheme and port', function (): void {
    $queryString = parse_url(navigationChildOriginFragmentUrl('https', 8443), PHP_URL_QUERY);
    throw_unless(is_string($queryString), RuntimeException::class, 'Expected a locator query string.');

    $this->get('https://localhost:8443/_capell/navigation/children?' . $queryString)
        ->assertSuccessful()
        ->assertSee('Public content');
});

it('does not constrain the scheme or port of a domain that stores neither', function (): void {
    $queryString = parse_url(navigationChildOriginFragmentUrl('http'), PHP_URL_QUERY);
    throw_unless(is_string($queryString), RuntimeException::class, 'Expected a locator query string.');
    SiteDomain::query()->update(['scheme' => null]);

    $this->get('https://localhost/_capell/navigation/children?' . $queryString)
        ->assertSuccessful();
});

it('builds lazy fragment cache keys outside the cache enum', function (): void {
    expect(NavigationCacheKeys::lazyFragmentKey('main|item'))
        ->toBe(NavigationCacheEnum::LazyFragments->value . '-' . hash('sha256', 'main|item'));
});

/**
 * @param  Collection<int, NavigationItemRenderData>  $items
 */
function navigationChildFragmentFirstItem(Collection $items): NavigationItemRenderData
{
    $item = $items->first();

    throw_unless($item instanceof NavigationItemRenderData, RuntimeException::class, 'Expected a navigation render item.');

    return $item;
}

function navigationChildFragmentUrl(?string $url): string
{
    throw_unless(is_string($url) && $url !== '', RuntimeException::class, 'Expected a navigation child fragment URL.');

    return $url;
}

function navigationChildOriginFragmentUrl(string $scheme = 'http', ?int $port = null, ?string $basePath = null): string
{
    $language = Language::factory()->default()->create();
    $site = Site::factory()->language($language)
        ->withTranslations(siteDomainData: ['scheme' => $scheme, 'domain' => 'localhost', 'port' => $port, 'path' => $basePath])
        ->create();
    $page = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $domain = $site->siteDomains()->where('language_id', $language->getKey())->firstOrFail();
    $navigation = Navigation::factory()->create([
        'key' => 'main',
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'items' => [[
            'key' => 'parent',
            'label' => 'Parent',
            'type' => NavigationItemType::Link->value,
            'data' => [
                'url' => '/parent',
                'children_loading' => NavigationChildrenLoadingEnum::Lazy->value,
            ],
            'children' => [[
                'key' => 'public',
                'label' => 'Public content',
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => '/public'],
            ]],
        ]],
    ])->refresh();
    $model = BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
        navigation: $navigation,
        page: $page,
        site: $site,
        language: $language,
        siteDomain: $domain,
    ));

    return navigationChildFragmentUrl(navigationChildFragmentFirstItem($model->items)->lazyFragmentUrl);
}
