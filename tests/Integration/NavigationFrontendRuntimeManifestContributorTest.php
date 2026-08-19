<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Data\FrontendContext;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Frontend\Enums\RenderingStrategyEnum;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\NavigationFrontendRuntimeManifestContributor;

it('enables the Alpine runtime when a blade page has a main navigation', function (): void {
    $language = Language::factory()->english()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations($language)
        ->create();
    $page = Page::factory()
        ->site($site)
        ->withTranslations(capell_test_collect([$language]))
        ->create();

    Navigation::factory()
        ->site($site)
        ->language($language)
        ->create(['key' => NavigationHandle::Main->value]);

    $context = new FrontendContext(
        site: $site,
        language: $language,
        page: $page,
        layout: null,
        theme: null,
        params: [],
        slug: null,
    );
    $manifest = FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly);

    (new NavigationFrontendRuntimeManifestContributor)->contribute($context, $manifest);

    expect($manifest->usesAlpine)->toBeTrue();
});
