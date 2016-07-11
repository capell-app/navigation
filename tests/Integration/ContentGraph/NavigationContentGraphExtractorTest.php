<?php

declare(strict_types=1);

use Capell\Core\Actions\ContentGraph\BuildContentGraphForModelAction;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeKind;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeStrength;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Carbon\CarbonImmutable;

it('extracts page dependencies from nested navigation items', function (): void {
    $publishedAt = CarbonImmutable::now()->subDay();
    $site = Site::factory()->withTranslations()->create();
    $page = Page::factory()->site($site)->published($publishedAt)->create();
    $childPage = Page::factory()->site($site)->published($publishedAt)->create();
    PageUrl::factory()->page($page)->site($site)->create(['language_id' => $site->language_id]);
    PageUrl::factory()->page($childPage)->site($site)->create(['language_id' => $site->language_id]);
    $navigation = Navigation::factory()->create([
        'site_id' => $page->site_id,
        'items' => [
            [
                'type' => NavigationItemType::Page->value,
                'label' => 'Parent',
                'data' => [
                    'pageable_type' => $page->getMorphClass(),
                    'pageable_id' => $page->id,
                ],
                'children' => [
                    [
                        'type' => NavigationItemType::Page->value,
                        'label' => 'Child',
                        'data' => [
                            'pageable_type' => $childPage->getMorphClass(),
                            'pageable_id' => $childPage->id,
                        ],
                        'children' => [],
                    ],
                ],
            ],
        ],
    ]);

    $edges = capell_test_collect(BuildContentGraphForModelAction::run($navigation)->edges);

    expect($edges)->toHaveCount(2)
        ->and($edges->pluck('kind')->unique()->all())->toBe([ContentGraphEdgeKind::LinksToPage])
        ->and($edges->pluck('strength')->unique()->all())->toBe([ContentGraphEdgeStrength::Strong])
        ->and($edges->pluck('siteId')->unique()->all())->toBe([(int) $navigation->site_id])
        ->and($edges->pluck('target.modelId')->sort()->values()->all())->toBe([
            $page->id,
            $childPage->id,
        ]);
});
