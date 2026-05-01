<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table): void {
            if (! Schema::hasColumn('workspaces', 'unpublish_at')) {
                $table->timestamp('unpublish_at')->nullable()->after('publish_at')->index();
            }

            if (! Schema::hasColumn('workspaces', 'embargo_until')) {
                $table->timestamp('embargo_until')->nullable()->after('unpublish_at')->index();
            }

            if (! Schema::hasColumn('workspaces', 'review_reminder_at')) {
                $table->timestamp('review_reminder_at')->nullable()->after('embargo_until')->index();
            }

            if (! Schema::hasIndex('workspaces', ['status', 'publish_at'])) {
                $table->index(['status', 'publish_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table): void {
            if (Schema::hasIndex('workspaces', ['status', 'publish_at'])) {
                $table->dropIndex(['status', 'publish_at']);
            }

            foreach (['unpublish_at', 'embargo_until', 'review_reminder_at'] as $column) {
                if (Schema::hasColumn('workspaces', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
