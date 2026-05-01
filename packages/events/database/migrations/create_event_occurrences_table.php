<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_occurrences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->nullable()->index();
            $table->string('timezone')->default('UTC');
            $table->string('status')->default('scheduled')->index();
            $table->json('location')->nullable();
            $table->json('booking')->nullable();
            $table->json('schema')->nullable();
            $table->boolean('is_cancelled')->default(false)->index();
            $table->timestamps();
            $table->index(['site_id', 'starts_at']);
            $table->index(['event_id', 'starts_at']);
            $table->unique(['event_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_occurrences');
    }
};
