<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_plugin_licenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketplace_plugin_id')->constrained('marketplace_plugins')->cascadeOnDelete();
            $table->text('encrypted_license_key')->nullable();
            $table->string('status');
            $table->json('metadata')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->integer('seats')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_plugin_licenses');
    }
};
