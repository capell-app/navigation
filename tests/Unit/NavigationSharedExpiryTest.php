<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Actions\ResolveNavigationCacheExpiryAction;
use Capell\Navigation\Actions\ScalarizeNavigationDataAction;
use Capell\Navigation\Data\NavigationRenderContextData;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

it('caps navigation cache at nested scheduled starts and expiry boundaries', function (): void {
    $now = CarbonImmutable::parse('2026-10-09 12:00:00');
    $items = ['children' => [['data' => ['visible_from' => '2026-10-09 12:00:30', 'visible_until' => '2026-10-09 12:01:00']]]];
    expect(ResolveNavigationCacheExpiryAction::make()->forItems($items, 300, $now))->toEqual($now->addSeconds(30));
});

it('ignores malformed and past dates while respecting the maximum ttl', function (): void {
    $now = CarbonImmutable::parse('2026-10-09 12:00:00');
    expect(ResolveNavigationCacheExpiryAction::make()->forItems(['visible_until' => 'not a date', 'visible_from' => '2020-01-01'], 300, $now))->toEqual($now->addSeconds(300));
});

it('scalarises nested enum navigation payloads and rejects object leakage', function (): void {
    expect(ScalarizeNavigationDataAction::run(['child' => ['type' => NavigationItemType::Link]]))->toBe(['child' => ['type' => NavigationItemType::Link->value]]);
    expect(fn () => ScalarizeNavigationDataAction::run(['secret' => new stdClass]))->toThrow(LogicException::class);
});

it('includes unpublished scheduled navigation records in the expiry query', function (): void {
    $now = CarbonImmutable::parse('2026-10-09 12:00:00');
    Navigation::factory()->create(['key' => 'scheduled-footer', 'visible_from' => $now->addSeconds(20), 'visible_until' => null]);
    expect(ResolveNavigationCacheExpiryAction::run(['scheduled-footer'], 300, $now))->toEqual($now->addSeconds(20));
});

it('expires a shared render model at a visibility boundary without a save event', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-09 12:00:00'));
    $language = Language::factory()->default()->create();
    $site = Site::factory()->language($language)->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])->create();
    $page = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $navigation = Navigation::factory()->create(['key' => 'boundary-test', 'site_id' => $site->id, 'language_id' => $language->id, 'items' => [['label' => 'Original', 'type' => NavigationItemType::Link->value, 'data' => ['url' => '/old', 'visible_until' => now()->addSeconds(20)->toDateTimeString()]]]]);
    $context = new NavigationRenderContextData(navigation: $navigation, page: $page, site: $site, language: $language, siteDomain: $site->siteDomains->first());
    $original = BuildNavigationRenderModelAction::run($context);
    expect($original->items->first()?->url)->toBe('/old');
    // A database change without a model event makes cache reuse observable.
    DB::table('navigations')->where('id', $navigation->id)->update(['items' => json_encode([['label' => 'Updated', 'type' => NavigationItemType::Link->value, 'data' => ['url' => '/new']]], JSON_THROW_ON_ERROR)]);
    $navigation->refresh();
    request()->attributes->remove('capell.navigation.render_models');
    expect(BuildNavigationRenderModelAction::run($context)->items->first()?->url)->toBe('/old');
    $this->travel(21)->seconds();
    request()->attributes->remove('capell.navigation.render_models');
    expect(BuildNavigationRenderModelAction::run($context)->items->first()?->url)->toBe('/new');
});
