<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('navigation_page_references')) {
            Schema::create('navigation_page_references', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('navigation_id')->constrained('navigations')->cascadeOnDelete();
                $table->foreignId('site_id')->nullable()->index();
                $table->foreignId('language_id')->nullable()->index();
                $table->string('pageable_type')->index();
                $table->unsignedBigInteger('pageable_id')->index();
                $table->timestamps();

                $table->unique(
                    ['navigation_id', 'pageable_type', 'pageable_id'],
                    'navigation_page_refs_unique',
                );
                $table->index(
                    ['pageable_type', 'pageable_id', 'site_id'],
                    'navigation_page_refs_lookup',
                );
            });
        }

        $this->backfillReferences();
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_page_references');
    }

    private function backfillReferences(): void
    {
        if (! Schema::hasTable('navigations') || ! Schema::hasTable('navigation_page_references')) {
            return;
        }

        DB::table('navigations')
            ->whereNull('deleted_at')
            ->select(['id', 'site_id', 'language_id', 'items'])
            ->orderBy('id')
            ->chunkById(100, function (Collection $navigations): void {
                foreach ($navigations as $navigation) {
                    if (! is_object($navigation)) {
                        continue;
                    }

                    $items = is_string($navigation->items) ? json_decode($navigation->items, true) : null;

                    if (! is_array($items)) {
                        continue;
                    }

                    $references = $this->extractReferences($items);

                    if ($references === []) {
                        continue;
                    }

                    $now = now();

                    DB::table('navigation_page_references')->insertOrIgnore(array_map(
                        static fn (array $reference): array => [
                            'navigation_id' => (int) $navigation->id,
                            'site_id' => is_numeric($navigation->site_id) ? (int) $navigation->site_id : null,
                            'language_id' => is_numeric($navigation->language_id) ? (int) $navigation->language_id : null,
                            'pageable_type' => $reference['pageable_type'],
                            'pageable_id' => $reference['pageable_id'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                        $references,
                    ));
                }
            });
    }

    /**
     * @param  array<array-key, mixed>  $items
     * @return list<array{pageable_type: string, pageable_id: int}>
     */
    private function extractReferences(array $items): array
    {
        $references = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $data = is_array($item['data'] ?? null) ? $item['data'] : [];
            $pageableType = $data['pageable_type'] ?? null;
            $pageableId = $data['pageable_id'] ?? null;

            if (is_string($pageableType) && $pageableType !== '' && is_numeric($pageableId)) {
                $references[$pageableType . ':' . (int) $pageableId] = [
                    'pageable_type' => $pageableType,
                    'pageable_id' => (int) $pageableId,
                ];
            }

            $children = is_array($item['children'] ?? null) ? $item['children'] : [];

            foreach ($this->extractReferences($children) as $reference) {
                $references[$reference['pageable_type'] . ':' . $reference['pageable_id']] = $reference;
            }
        }

        return array_values($references);
    }
};
