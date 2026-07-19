<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Core\Contracts\SiteSpec\SiteSpecApplier;
use Capell\Core\Data\SiteSpec\CapellSiteSpecData;
use Capell\Core\Data\SiteSpec\CapellSiteSpecNavigationData;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Support\Creator\NavigationCreator;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/**
 * @method static void run(CapellSiteSpecData $spec, Site $site, array<string, Page> $pagesBySlug)
 */
final class ApplyNavigationSiteSpecAction implements SiteSpecApplier
{
    use AsFake;
    use AsObject;

    public function __construct(
        private readonly NavigationCreator $navigationCreator,
    ) {}

    public function key(): string
    {
        return 'navigation';
    }

    /** @param array<string, Page> $pagesBySlug */
    public function apply(CapellSiteSpecData $spec, Site $site, array $pagesBySlug): void
    {
        $this->handle($spec, $site, $pagesBySlug);
    }

    /** @param array<string, Page> $pagesBySlug */
    public function handle(CapellSiteSpecData $spec, Site $site, array $pagesBySlug): void
    {
        $language = $site->language;

        throw_unless($language instanceof Language, RuntimeException::class, 'Unable to resolve a language for SiteSpec navigation application.');

        foreach ($spec->navigations as $navigationSpec) {
            $navigation = $this->navigationCreator->footerNavigation(
                site: $site,
                language: $language,
                key: $navigationSpec->key,
            );

            $navigation->forceFill([
                'name' => $navigationSpec->name ?? $navigation->name,
                'items' => $this->items($navigationSpec, $site, $language, $pagesBySlug),
            ])->save();
        }
    }

    /**
     * @param  array<string, Page>  $pagesBySlug
     * @return list<array<string, mixed>>
     */
    private function items(
        CapellSiteSpecNavigationData $navigationSpec,
        Site $site,
        Language $language,
        array $pagesBySlug,
    ): array {
        return array_map(function (string $pageSlug) use ($language, $navigationSpec, $pagesBySlug, $site): array {
            $page = $pagesBySlug[$pageSlug] ?? null;

            throw_unless($page instanceof Page, RuntimeException::class, sprintf(
                'Navigation [%s] references missing page slug [%s].',
                $navigationSpec->key,
                $pageSlug,
            ));

            return [
                'label' => NavigationCreator::getPageNavigationLabel($page, $language),
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'site_id' => $site->getKey(),
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
                'children' => [],
            ];
        }, $navigationSpec->pageSlugs);
    }
}
