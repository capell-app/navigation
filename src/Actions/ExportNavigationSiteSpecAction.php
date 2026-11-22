<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Core\Contracts\SiteSpec\SiteSpecExporter;
use Capell\Core\Data\SiteSpec\CapellSiteSpecNavigationData;
use Capell\Core\Data\SiteSpec\SiteSpecExportPartData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Providers\NavigationServiceProvider;
use Capell\Navigation\Support\Creator\NavigationCreator;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Override;

/**
 * Writes the site's navigations into a SiteSpec export, the counterpart of
 * {@see ApplyNavigationSiteSpecAction}. Only flat page links that the applier
 * rebuilds exactly are written; everything else is listed in `omitted`.
 *
 * @method static SiteSpecExportPartData run(Site $site, array<int, string> $pageSlugsById, bool $public)
 */
final class ExportNavigationSiteSpecAction implements SiteSpecExporter
{
    use AsFake;
    use AsObject;

    /** Item keys the applier writes or that only identify the item; anything else is not restored. */
    private const array RestoredItemKeys = ['label', 'type', 'data', 'children', 'key', 'active', 'is_visible'];

    /** Data keys the applier writes for a page item. */
    private const array RestoredDataKeys = ['site_id', 'pageable_id', 'pageable_type'];

    #[Override]
    public function key(): string
    {
        return 'navigation';
    }

    /** @param array<int, string> $pageSlugsById */
    #[Override]
    public function export(Site $site, array $pageSlugsById, bool $public): SiteSpecExportPartData
    {
        return $this->handle($site, $pageSlugsById, $public);
    }

    /** @param array<int, string> $pageSlugsById */
    public function handle(Site $site, array $pageSlugsById, bool $public): SiteSpecExportPartData
    {
        if (! CapellCore::isPackageEnabled(NavigationServiceProvider::$packageName)) {
            return new SiteSpecExportPartData;
        }

        $language = $site->language;

        if (! $language instanceof Language) {
            return new SiteSpecExportPartData(omitted: ['Navigation: the site has no language, so no navigation could be exported.']);
        }

        $navigations = [];
        $omitted = [];

        $models = Navigation::query()
            ->with('blueprint')
            ->where('site_id', $site->getKey())
            ->orderBy('key')
            ->orderBy('id')
            ->get();

        foreach ($models as $navigation) {
            $reason = $this->navigationOmissionReason($navigation, $language);

            if ($reason !== null) {
                $omitted[] = sprintf('Navigation [%s]: %s', $navigation->key, $reason);

                continue;
            }

            $pageSlugs = $this->pageSlugs($navigation, $site, $language, $pageSlugsById, $public, $omitted);

            if ($pageSlugs === []) {
                $omitted[] = sprintf('Navigation [%s]: it has no page links that a SiteSpec can restore.', $navigation->key);

                continue;
            }

            $navigations[] = new CapellSiteSpecNavigationData(
                key: (string) $navigation->key,
                pageSlugs: $pageSlugs,
                name: $navigation->name,
            );
        }

        return new SiteSpecExportPartData(navigations: $navigations, omitted: $omitted);
    }

    private function integer(mixed $value): ?int
    {
        return is_int($value) || (is_string($value) && ctype_digit($value)) ? (int) $value : null;
    }

    private function text(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    private function navigationOmissionReason(Navigation $navigation, Language $language): ?string
    {
        if ($this->integer($navigation->language_id) !== $this->integer($language->getKey())) {
            return 'it is not in the site language; a SiteSpec only carries site-language navigations.';
        }

        if ($navigation->visible_from !== null || $navigation->visible_until !== null) {
            return 'it has a visibility window, which a SiteSpec cannot carry.';
        }

        if (filled($navigation->meta)) {
            return 'it has settings (meta), which a SiteSpec cannot carry.';
        }

        $blueprintKey = $navigation->blueprint?->key;

        if ($blueprintKey !== null && $blueprintKey !== 'navigation') {
            return sprintf('it uses the [%s] blueprint; a SiteSpec only restores the default navigation blueprint.', $blueprintKey);
        }

        return null;
    }

    /**
     * @param  array<int, string>  $pageSlugsById
     * @param  list<string>  $omitted
     * @return list<string>
     */
    private function pageSlugs(Navigation $navigation, Site $site, Language $language, array $pageSlugsById, bool $public, array &$omitted): array
    {
        $rawItems = json_decode($this->text($navigation->getRawOriginal('items')), true);
        $rawItems = is_array($rawItems) ? array_values($rawItems) : [];

        $candidates = [];

        foreach ($rawItems as $position => $item) {
            $item = is_array($item) ? $item : [];
            $label = is_string($item['label'] ?? null) ? $item['label'] : '';
            $describe = sprintf('Navigation [%s] item %d%s', $navigation->key, $position + 1, $label !== '' ? sprintf(' "%s"', $label) : '');
            $reason = $this->itemOmissionReason($item, $site, $pageSlugsById);

            if ($reason !== null) {
                $omitted[] = sprintf('%s: %s', $describe, $reason);

                continue;
            }

            /** @var array{pageable_id: int|string} $data */
            $data = $item['data'];
            $candidates[] = ['id' => (int) $data['pageable_id'], 'label' => $item['label'] ?? null, 'describe' => $describe];
        }

        $pages = Page::query()
            ->with('translations')
            ->whereKey(array_column($candidates, 'id'))
            ->get()
            ->each(function (Page $page) use ($site): void {
                $page->setRelation('site', $site);
            })
            ->keyBy(fn (Page $page): int => $this->integer($page->getKey()) ?? 0);

        $publicIds = $public
            ? ResolvePublicNavigationPagesAction::run(Page::class, array_values(array_unique(array_column($candidates, 'id'))), $this->integer($site->getKey()) ?? 0)
                ->map(fn (Model $page): ?int => $this->integer($page->getKey()))
                ->all()
            : null;

        $slugs = [];

        foreach ($candidates as $candidate) {
            $page = $pages->get($candidate['id']);

            if (! $page instanceof Page) {
                $omitted[] = sprintf('%s: its page no longer exists.', $candidate['describe']);

                continue;
            }

            if ($publicIds !== null && ! in_array($candidate['id'], $publicIds, true)) {
                $omitted[] = sprintf('%s: its page is not public, so it is left out of a public export.', $candidate['describe']);

                continue;
            }

            if ($candidate['label'] !== NavigationCreator::getPageNavigationLabel($page, $language)) {
                $omitted[] = sprintf('%s: it has a custom label, which a SiteSpec cannot carry.', $candidate['describe']);

                continue;
            }

            $slug = $pageSlugsById[$candidate['id']];

            if (in_array($slug, $slugs, true)) {
                $omitted[] = sprintf('%s: it repeats a page already in this navigation, which a SiteSpec cannot carry.', $candidate['describe']);

                continue;
            }

            $slugs[] = $slug;
        }

        return $slugs;
    }

    /**
     * @param  array<array-key, mixed>  $item
     * @param  array<int, string>  $pageSlugsById
     */
    private function itemOmissionReason(array $item, Site $site, array $pageSlugsById): ?string
    {
        if (($item['type'] ?? null) !== NavigationItemType::Page->value) {
            return sprintf('it is a [%s] item; a SiteSpec only carries page links.', is_string($item['type'] ?? null) ? $item['type'] : 'unknown');
        }

        if (array_diff(array_keys($item), self::RestoredItemKeys) !== []) {
            return 'it has fields a SiteSpec cannot carry.';
        }

        if (filled($item['children'] ?? null)) {
            return 'it has child items; a SiteSpec only carries a flat list.';
        }

        if (($item['is_visible'] ?? true) !== true) {
            return 'it is hidden, which a SiteSpec cannot carry.';
        }

        if (($item['active'] ?? null) !== null) {
            return 'it has stored active state, which a SiteSpec cannot carry.';
        }

        // The item key reaches render data, and the applier never writes it back.
        if (filled($item['key'] ?? null)) {
            return 'it has an item key, which a SiteSpec cannot carry.';
        }

        $data = $item['data'] ?? null;

        if (! is_array($data) || array_diff(array_keys($data), self::RestoredDataKeys) !== []) {
            return 'it has link settings a SiteSpec cannot carry.';
        }

        if (($data['pageable_type'] ?? null) !== (new Page)->getMorphClass() || ! is_numeric($data['pageable_id'] ?? null)) {
            return 'it does not link to a page.';
        }

        if (isset($data['site_id']) && $this->integer($data['site_id']) !== $this->integer($site->getKey())) {
            return 'it links to a page on another site.';
        }

        if (! array_key_exists((int) $data['pageable_id'], $pageSlugsById)) {
            return 'its page is not in this export.';
        }

        return null;
    }
}
