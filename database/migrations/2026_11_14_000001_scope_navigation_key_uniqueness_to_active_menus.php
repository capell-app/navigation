<?php

declare(strict_types=1);

use Capell\Core\Support\TableName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The original unique index on (key, site_id, language_id) treats NULLs as distinct, so two menus for all sites or
 * all languages could share a key, while a deleted menu kept its key and blocked a replacement. Uniqueness now lives
 * on one column that holds the scope of every live menu and is cleared when the menu is deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = TableName::resolve('navigations');

        if (! Schema::hasTable($table)) {
            return;
        }

        if (! Schema::hasColumn($table, 'active_key_scope')) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->char('active_key_scope', 64)->nullable()->after('key');
            });
        }

        $this->backfillScopes($table);

        $legacyIndex = TableName::namedIndex('navigations', 'navigations_key_site_language_unique');

        if (Schema::hasIndex($table, $legacyIndex)) {
            Schema::table($table, function (Blueprint $blueprint) use ($legacyIndex): void {
                $blueprint->dropUnique($legacyIndex);
            });
        }

        $scopeIndex = TableName::namedIndex('navigations', 'navigations_active_key_scope_unique');

        if (! Schema::hasIndex($table, $scopeIndex)) {
            Schema::table($table, function (Blueprint $blueprint) use ($scopeIndex): void {
                $blueprint->unique('active_key_scope', $scopeIndex);
            });
        }
    }

    public function down(): void
    {
        // Restoring the NULL-blind index could fail on menus created since, so the scope column stays.
    }

    /**
     * Live menus already sharing a scope keep the oldest one as the holder of the key; the newer duplicates stay
     * live and keep rendering as before, but cannot be saved until an editor gives them a different key.
     */
    private function backfillScopes(string $table): void
    {
        $claimed = [];

        DB::table($table)
            ->whereNull('deleted_at')
            ->select(['id', 'key', 'site_id', 'language_id'])
            ->orderBy('id')
            ->chunkById(200, function (Collection $navigations) use ($table, &$claimed): void {
                foreach ($navigations as $navigation) {
                    if (! is_object($navigation) || ! is_string($navigation->key) || $navigation->key === '') {
                        continue;
                    }

                    // Same value as NavigationKeyScope::for(), kept inline so this migration never depends on package code.
                    $scope = hash('sha256', implode('|', [
                        $navigation->key,
                        is_numeric($navigation->site_id) ? (string) (int) $navigation->site_id : 'all',
                        is_numeric($navigation->language_id) ? (string) (int) $navigation->language_id : 'all',
                    ]));

                    if (isset($claimed[$scope])) {
                        continue;
                    }

                    $claimed[$scope] = true;

                    DB::table($table)->where('id', $navigation->id)->update(['active_key_scope' => $scope]);
                }
            });
    }
};
