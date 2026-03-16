<?php

declare(strict_types=1);

use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Enums\ModelEnum as BlogModelEnum;
use Capell\Blog\Models\Article;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

use function Pest\Laravel\artisan;

it('runs demo command and creates articles and tags for the site', function (): void {
    /** @var Language $language */
    $language = Language::factory()->create([
        'code' => 'en',
    ]);

    /** @var Site $site */
    $site = Site::factory()->language($language)->withTranslations()->create();

    // Ensure the site has language relations loaded
    $site->refresh();
    $site->loadMissing('languages', 'language');

    // Run the demo command for this site with a small limit
    artisan('capell:blog-demo', [
        '--sites' => $site->name,
        '--limit' => 2,
    ])
        ->expectsOutput('Setting up demo blog for site: ' . $site->name)
        ->expectsOutput('Blog demo setup completed for selected sites.')
        ->assertExitCode(Command::SUCCESS);

    // Verify that article pages were created under the blog page for the site
    $articleModel = CapellCore::getModel(BlogModelEnum::Article);

    /** @var Collection<int, Article> $articles */
    $articles = $articleModel::query()
        ->where('site_id', $site->id)
        ->whereRelation('type', 'key', BlogPageTypeEnum::Article->value)
        ->get();

    expect($articles->count())->toBeGreaterThanOrEqual(1);

    // Verify that tags were created for pages
    $tagModel = CapellCore::getModel(BlogModelEnum::Tag);
    $tagsCount = $tagModel::query()->count();
    expect($tagsCount)->toBeGreaterThanOrEqual(1);

    // Verify that at least one created article has tags associated
    $articleWithTags = $articles->first(fn (Pageable $article): bool => $article->tags()->exists());

    expect($articleWithTags)->not()->toBeNull();
});
