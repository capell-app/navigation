<?php

declare(strict_types=1);

namespace Capell\Navigation\Support\Creator;

use Capell\Core\Actions\ResolvePageableMorphModelAction;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Events\NavigationCreating;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\LaravelData\DataCollection;

class NavigationCreator
{
    /**
     * @var class-string<Navigation>
     */
    private readonly string $navigationModel;

    /**
     * @var class-string<Blueprint>
     */
    private readonly string $typeModel;

    public function __construct()
    {
        $this->navigationModel = Navigation::class;
        $this->typeModel = Blueprint::class;
    }

    public static function getPageNavigationLabel(Page $page, ?Language $language = null): string
    {
        if (! $language instanceof Language) {
            $language = $page->site?->language;
        }

        if (! $language instanceof Language) {
            return $page->name;
        }

        $translation = $page->translations->firstWhere('language_id', $language->id);

        $label = $translation instanceof Model ? $translation->label : null;

        if ($label) {
            return self::stripSiteNameSuffix($page, $label);
        }

        $title = $translation instanceof Model ? $translation->title : null;

        if ($title) {
            return self::stripSiteNameSuffix($page, $title);
        }

        return $page->name;
    }

    /**
     * @param  Collection<array-key, Page>  $pages
     * @param  array<array-key, array<string, mixed>>  $items
     */
    public function footerNavigation(
        Site $site,
        ?Blueprint $type = null,
        ?Language $language = null,
        ?Collection $pages = null,
        array $items = [],
        string $key = NavigationHandle::Footer->value,
    ): Navigation {
        if (! $language instanceof Language) {
            $language = $site->language;
        }

        throw_unless($language instanceof Language, RuntimeException::class, 'Unable to resolve a language for main navigation creation.');

        $navigation = self::createNavigation($key, $site, $language, $type);

        $items = $this->normalizePageLabels($this->navigationItemsFromValue($navigation->items), $language)
            ->merge($items);

        if ($pages instanceof Collection && $pages->isNotEmpty()) {
            $pages->each(function (Page $page) use (&$items, $language): void {
                $existingItem = $items->first(function (array $candidate) use ($page): bool {
                    $reference = self::pageReference($candidate);

                    return $reference !== null
                        && (int) $reference['pageable_id'] === $page->getKey()
                        && $reference['pageable_type'] === $page->getMorphClass();
                });

                if ($existingItem !== null) {
                    return;
                }

                $items->put((string) Str::uuid(), [
                    'label' => self::getPageNavigationLabel($page, $language),
                    'type' => NavigationItemType::Page->value,
                    'data' => [
                        'site_id' => $page->site_id,
                        'pageable_id' => $page->getKey(),
                        'pageable_type' => $page->getMorphClass(),
                    ],
                    'children' => [],
                ]);
            });
        }

        event(new NavigationCreating($navigation, $items));

        $navigation->items = $items->all();
        $navigation->save();

        return $navigation;
    }

    /**
     * @param  Collection<array-key, Page>  $pages
     * @param  array<array-key, array<string, mixed>>  $items
     */
    public function subFooterNavigation(
        Site $site,
        ?Blueprint $type = null,
        ?Language $language = null,
        ?Collection $pages = null,
        array $items = [],
        string $key = NavigationHandle::SubFooter->value,
    ): Navigation {
        return $this->footerNavigation(site: $site, type: $type, language: $language, pages: $pages, items: $items, key: $key);
    }

    /**
     * @param  array<array-key, array<string, mixed>>  $additionalItems
     */
    public function mainNavigation(
        Site $site,
        ?Blueprint $type = null,
        ?Language $language = null,
        ?Page $home = null,
        array $additionalItems = [],
        string $key = NavigationHandle::Main->value,
    ): Navigation {
        if (! $language instanceof Language) {
            $language = $site->language;
        }

        throw_unless($language instanceof Language, RuntimeException::class, 'Unable to resolve a language for main navigation creation.');

        $navigation = self::createNavigation($key, $site, $language, $type);

        $items = $this->normalizePageLabels($this->navigationItemsFromValue($navigation->items), $language);

        $homePageExists = $home instanceof Page
            ? $items->first(
                function (array $candidate) use ($home): bool {
                    $reference = self::pageReference($candidate);

                    return $reference !== null
                        && (int) $reference['pageable_id'] === $home->getKey()
                        && $reference['pageable_type'] === $home->getMorphClass();
                },
            )
            : null;

        if ($home instanceof Page && $homePageExists === null) {
            $items->prepend(
                [
                    'label' => self::getPageNavigationLabel($home, $language),
                    'type' => NavigationItemType::Page->value,
                    'data' => [
                        'site_id' => $home->site_id,
                        'pageable_id' => $home->id,
                        'pageable_type' => $home->getMorphClass(),
                        'hidden_label' => true,
                        'icon' => 'heroicon-o-home',
                    ],
                    'children' => [],
                ],
                (string) Str::uuid(),
            );
        }

        foreach ($additionalItems as $item) {
            $itemReference = self::pageReference($item);

            if ($itemReference !== null) {
                $pageExistsKey = $items->search(function (array $candidate) use ($itemReference): bool {
                    $reference = self::pageReference($candidate);

                    return $reference !== null
                        && (int) $reference['pageable_id'] === (int) $itemReference['pageable_id']
                        && $reference['pageable_type'] === $itemReference['pageable_type'];
                });

                if ($pageExistsKey !== false) {
                    $existingItem = $items->get($pageExistsKey);

                    if (($existingItem['label'] ?? null) === null || $existingItem['label'] === '') {
                        $existingItem['label'] = $item['label'];
                        $items->put($pageExistsKey, $existingItem);
                    }

                    continue;
                }
            }

            $items->put((string) Str::uuid(), [
                'label' => $item['label'],
                'type' => $item['type'],
                'data' => $item['data'],
                'children' => $item['children'] ?? [],
            ]);
        }

        $navigation->items = $items->all();

        $navigation->save();

        return $navigation;
    }

    private static function stripSiteNameSuffix(Page $page, string $value): string
    {
        $value = trim($value);

        if (! str_contains($value, '|')) {
            return $value;
        }

        $site = $page->relationLoaded('site') ? $page->getRelation('site') : null;
        $siteName = trim((string) ($site instanceof Site ? $site->name : null));

        if ($siteName === '') {
            return $value;
        }

        $segments = preg_split('/\s*\|\s*/u', $value, flags: PREG_SPLIT_NO_EMPTY);

        if (! is_array($segments)) {
            return $value;
        }

        $siteNameIndex = array_search($siteName, $segments, true);

        if (! is_int($siteNameIndex) || $siteNameIndex === 0) {
            return $value;
        }

        return trim(implode(' | ', array_slice($segments, 0, $siteNameIndex)));
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function navigationItemArray(mixed $item): ?array
    {
        if (! is_array($item)) {
            return null;
        }

        $normalized = [];

        foreach ($item as $key => $value) {
            if (! is_string($key)) {
                return null;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{pageable_id: int|string, pageable_type: string}|null
     */
    private static function pageReference(array $item): ?array
    {
        $data = $item['data'] ?? null;

        if (! is_array($data)) {
            return null;
        }

        $pageableId = $data['pageable_id'] ?? null;
        $pageableType = $data['pageable_type'] ?? null;

        if ((! is_int($pageableId) && ! is_string($pageableId)) || ! is_string($pageableType)) {
            return null;
        }

        return [
            'pageable_id' => $pageableId,
            'pageable_type' => $pageableType,
        ];
    }

    /**
     * @return Collection<array-key, array<string, mixed>>
     */
    private function navigationItemsFromValue(mixed $items): Collection
    {
        if ($items instanceof DataCollection) {
            $items = $items->toArray();
        }

        if (! is_array($items)) {
            return new Collection;
        }

        $normalized = [];

        foreach ($items as $key => $item) {
            $normalizedItem = self::navigationItemArray($item);

            if ($normalizedItem !== null) {
                $normalized[$key] = $normalizedItem;
            }
        }

        return new Collection($normalized);
    }

    /**
     * @param  Collection<array-key, array<string, mixed>>  $items
     * @return Collection<array-key, array<string, mixed>>
     */
    private function normalizePageLabels(Collection $items, Language $language): Collection
    {
        return $items->map(function (array $item) use ($language): array {
            $reference = self::pageReference($item);

            if ($reference !== null) {
                $page = ResolvePageableMorphModelAction::run(
                    $reference['pageable_type'],
                    $reference['pageable_id'],
                );

                if ($page instanceof Page) {
                    $page->loadMissing('translations', 'site');
                    $label = $item['label'] ?? null;
                    $item['label'] = is_string($label) && trim($label) !== ''
                        ? self::stripSiteNameSuffix($page, $label)
                        : self::getPageNavigationLabel($page, $language);
                }
            }

            if (is_array($item['children'] ?? null) && $item['children'] !== []) {
                $item['children'] = $this->normalizePageLabels($this->navigationItemsFromValue($item['children']), $language)->all();
            }

            return $item;
        });
    }

    /**
     * @return Builder<Navigation>
     */
    private function navigationQuery(): Builder
    {
        return $this->navigationModel::query();
    }

    /**
     * @return Builder<Blueprint>
     */
    private function typeQuery(): Builder
    {
        return $this->typeModel::query();
    }

    private function createNavigation(string $key, Site $site, ?Language $language = null, ?Blueprint $type = null): Navigation
    {
        $languageId = $language instanceof Language ? (int) $language->id : null;

        $navigation = $this->navigationModel::query()
            ->where([
                'key' => $key,
                'site_id' => $site->id,
            ])
            ->when(
                $languageId !== null,
                fn (Builder $query): Builder => $query->where('language_id', $languageId),
                fn (Builder $query): Builder => $query->whereNull('language_id'),
            )
            ->first();

        if ($navigation !== null) {
            return $navigation;
        }

        // Use typed Blueprint builder so scopes are recognized
        $type ??= $this->typeQuery()->where('type', 'navigation')->first();
        if ($type === null) {
            $type = $this->typeQuery()->create([
                'key' => 'navigation',
                'type' => 'navigation',
                'name' => 'Navigation',
            ]);
        }

        return $this->navigationQuery()->make([
            'name' => Str::title($key),
            'blueprint_id' => $type->id,
            'key' => $key,
            'site_id' => $site->id,
            'language_id' => $language?->id,
        ]);
    }
}
