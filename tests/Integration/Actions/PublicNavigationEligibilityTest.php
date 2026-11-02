<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Dom\HTMLDocument;

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
        compact('site', 'language', 'currentPage', 'domain'),
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
