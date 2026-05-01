<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table): void {
            if (! Schema::hasColumn('forms', 'handle')) {
                $table->string('handle')->nullable()->after('name')->index();
            }

            if (! Schema::hasColumn('forms', 'schema')) {
                $table->json('schema')->nullable()->after('description');
            }

            if (! Schema::hasColumn('forms', 'settings')) {
                $table->json('settings')->nullable()->after('schema');
            }
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table): void {
            foreach (['handle', 'schema', 'settings'] as $column) {
                if (Schema::hasColumn('forms', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
