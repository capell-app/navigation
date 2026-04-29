<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('authentication_log')) {
            return;
        }

        if (Schema::hasIndex('authentication_log', 'authenticatable_login_at_index')) {
            return;
        }

        Schema::table('authentication_log', function (Blueprint $table): void {
            $table->index(
                ['authenticatable_type', 'authenticatable_id', 'login_at'],
                'authenticatable_login_at_index',
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('authentication_log')) {
            return;
        }

        if (! Schema::hasIndex('authentication_log', 'authenticatable_login_at_index')) {
            return;
        }

        Schema::table('authentication_log', function (Blueprint $table): void {
            $table->dropIndex('authenticatable_login_at_index');
        });
    }
};
