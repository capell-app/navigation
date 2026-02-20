<?php

declare(strict_types=1);

use Capell\Blog\Actions\CreateBlogPagesAction;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;

it('creates all required blog pages and links them for a site', function (): void {
    $site = Site::factory()->create();

    // Ensure required Layouts and Types exist for the action to find
    $archivesLayout = Layout::factory()->create(['key' => 'archives']);
    $resultsLayout = Layout::factory()->create(['key' => 'results']);
    $blogLayout = Layout::factory()->create(['key' => 'blog']);
    $archivePageType = Type::factory()->create(['key' => 'archive', 'type' => 'page']);
    $blogPageType = Type::factory()->create(['key' => 'blog', 'type' => 'page']);
    $systemPageType = Type::factory()->create(['key' => 'system', 'type' => 'page']);
    $tagPageType = Type::factory()->create(['key' => 'tag', 'type' => 'page']);

    CreateBlogPagesAction::run($site);

    // --- Assertions ---
    $blogPage = $site->pages()->where('type_id', $blogPageType->id)->first();
    $blogPageLayout = $blogPage ? Layout::query()->find($blogPage->layout_id) : null;
    $archivesPage = $blogPage ? $site->pages()->where('type_id', $systemPageType->id)->where('parent_id', $blogPage->id)->first() : null;
    $archivePage = $archivesPage ? $site->pages()->where('type_id', $archivePageType->id)->where('parent_id', $archivesPage->id)->first() : null;
    $tagsPage = $site->pages()->where('type_id', $systemPageType->id)->whereNull('parent_id')->where('name', 'like', '%tags%')->first();
    $tagPage = $tagsPage ? $site->pages()->where('type_id', $tagPageType->id)->where('parent_id', $tagsPage->id)->first() : null;

    expect($blogPage)->not()->toBeNull()
        ->and($blogPageLayout)->not()->toBeNull()->and($blogPageLayout->key)->toBe('blog-results')
        ->and($archivesPage)->not()->toBeNull()
        ->and($archivesPage?->layout_id)->toBe($archivesLayout->id)
        ->and($archivePage)->not()->toBeNull()
        ->and($archivePage?->layout_id)->toBe($resultsLayout->id)
        ->and($tagsPage)->not()->toBeNull()
        ->and($tagPage)->not()->toBeNull();
});
