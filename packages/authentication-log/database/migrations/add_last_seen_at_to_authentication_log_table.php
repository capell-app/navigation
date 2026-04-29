<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('authentication_log')) {
            return;
        }

        if (! Schema::hasColumn('authentication_log', 'last_seen_at')) {
            Schema::table('authentication_log', function (Blueprint $table): void {
                $table->timestamp('last_seen_at')->nullable();
            });
        }

        DB::table('authentication_log')->update([
            'last_seen_at' => DB::raw('CASE WHEN login_at > logout_at THEN login_at ELSE logout_at END'),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('authentication_log')) {
            return;
        }

        if (! Schema::hasColumn('authentication_log', 'last_seen_at')) {
            return;
        }

        Schema::table('authentication_log', function (Blueprint $table): void {
            $table->dropColumn('last_seen_at');
        });
    }
};
