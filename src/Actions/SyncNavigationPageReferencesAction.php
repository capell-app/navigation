<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Models\Navigation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsObject;
use Spatie\LaravelData\DataCollection;

/**
 * @method static int run(Navigation $navigation)
 */
final class SyncNavigationPageReferencesAction
{
    use AsObject;

    public function handle(Navigation $navigation): int
    {
        if (! $navigation->exists || ! Schema::hasTable('navigation_page_references')) {
            return 0;
        }

        $references = $this->extractReferences($navigation->items);
        $now = now();

        DB::transaction(function () use ($navigation, $now, $references): void {
            DB::table('navigation_page_references')
                ->where('navigation_id', (int) $navigation->getKey())
                ->delete();

            if ($references === []) {
                return;
            }

            DB::table('navigation_page_references')->insert(array_map(
                static fn (array $reference): array => [
                    'navigation_id' => (int) $navigation->getKey(),
                    'site_id' => is_numeric($navigation->site_id) ? (int) $navigation->site_id : null,
                    'language_id' => is_numeric($navigation->language_id) ? (int) $navigation->language_id : null,
                    'pageable_type' => $reference['pageable_type'],
                    'pageable_id' => $reference['pageable_id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $references,
            ));
        });

        BuildPageNavigationReferencesAction::flushRequestCache();

        return count($references);
    }

    /**
     * @return list<array{pageable_type: string, pageable_id: int}>
     */
    private function extractReferences(mixed $items): array
    {
        if ($items instanceof DataCollection) {
            $items = $items->all();
        }

        if (! is_iterable($items)) {
            return [];
        }

        $references = [];

        foreach ($items as $item) {
            if ($item instanceof NavigationItemData) {
                $this->mergeReference($references, $item->data);

                foreach ($this->extractReferences($item->children) as $reference) {
                    $references[$reference['pageable_type'] . ':' . $reference['pageable_id']] = $reference;
                }

                continue;
            }

            if (! is_array($item)) {
                continue;
            }

            $data = is_array($item['data'] ?? null) ? $item['data'] : [];
            $this->mergeReference($references, $data);

            foreach ($this->extractReferences($item['children'] ?? []) as $reference) {
                $references[$reference['pageable_type'] . ':' . $reference['pageable_id']] = $reference;
            }
        }

        return array_values($references);
    }

    /**
     * @param  array<string, array{pageable_type: string, pageable_id: int}>  $references
     * @param  array<string, mixed>  $data
     */
    private function mergeReference(array &$references, array $data): void
    {
        $pageableType = $data['pageable_type'] ?? null;
        $pageableId = $data['pageable_id'] ?? null;

        if (! is_string($pageableType) || $pageableType === '' || ! is_numeric($pageableId)) {
            return;
        }

        $references[$pageableType . ':' . (int) $pageableId] = [
            'pageable_type' => $pageableType,
            'pageable_id' => (int) $pageableId,
        ];
    }
}
