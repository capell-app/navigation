<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_plugins', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('composer_name')->unique();
            $table->string('title');
            $table->string('vendor');
            $table->text('description')->nullable();
            $table->string('kind');
            $table->string('license_model');
            $table->json('categories')->nullable();
            $table->json('screenshots')->nullable();
            $table->json('compatibility')->nullable();
            $table->json('capabilities')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->integer('price_monthly')->nullable();
            $table->integer('price_yearly')->nullable();
            $table->integer('price_once')->nullable();
            $table->integer('trial_days')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_plugins');
    }
};
