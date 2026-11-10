<?php

declare(strict_types=1);

use Capell\Core\Support\TableName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (TableName::existsAndIsOwned('navigations', basename(__FILE__, '.php'))) {
            return;
        }

        Schema::create(TableName::resolve('navigations'), function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('key')->index();
            $table->foreignId('blueprint_id')->constrained(TableName::resolve('blueprints'));
            $table->foreignId('site_id')->nullable()->constrained(TableName::resolve('sites'))->cascadeOnDelete();
            $table->foreignId('language_id')->nullable()->constrained(TableName::resolve('languages'))->cascadeOnDelete();
            $table->json('items')->nullable();
            $table->json('meta')->nullable();
            $table->visibleDates();
            $table->userstamps();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['site_id', 'language_id']);
            $table->unique(['key', 'site_id', 'language_id'], TableName::namedIndex('navigations', 'navigations_key_site_language_unique'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(TableName::resolve('navigations'));
    }
};
