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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\artisan;

it('runs demo command and creates articles and tags for the site', function (): void {
    $capellDirectory = storage_path('app/capell');
    $demoDirectory = $capellDirectory . '/demo';
    $stagingDirectory = $capellDirectory . '/demo-test-assets';
    $demoZipPath = $capellDirectory . '/demo-test.zip';
    $imageDirectory = $stagingDirectory . '/demo/img';
    $imagePath = $imageDirectory . '/sample.jpg';

    File::deleteDirectory($demoDirectory);
    File::deleteDirectory($stagingDirectory);
    File::delete($demoZipPath);

    File::ensureDirectoryExists($imageDirectory);

    $generatedImage = imagecreatetruecolor(16, 16);
    $backgroundColor = imagecolorallocate($generatedImage, 40, 110, 180);
    imagefill($generatedImage, 0, 0, $backgroundColor);
    imagejpeg($generatedImage, $imagePath, 90);
    imagedestroy($generatedImage);

    $archive = new ZipArchive;
    $archive->open($demoZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $archive->addFile($imagePath, 'demo/img/sample.jpg');
    $archive->close();

    Http::fake([
        'https://capell.app/demo.zip' => Http::response(
            File::get($demoZipPath),
            200,
            ['Content-Type' => 'application/zip'],
        ),
    ]);

    /** @var Language $language */
    $language = Language::factory()->create([
        'code' => 'en',
    ]);

    /** @var Site $site */
    $site = Site::factory()->language($language)->withTranslations()->create();

    $site->refresh();
    $site->loadMissing('languages', 'language');

    artisan('capell:blog-demo', [
        '--sites' => $site->name,
        '--limit' => 2,
    ])
        ->expectsOutput('Setting up demo blog for site: ' . $site->name)
        ->expectsOutput('Blog demo setup completed for selected sites.')
        ->assertExitCode(Command::SUCCESS);

    Http::assertSentCount(1);

    /** @var class-string<Article> $articleModel */
    $articleModel = CapellCore::getModel(BlogModelEnum::Article);

    /** @var Collection<int, Article> $articles */
    $articles = $articleModel::query()
        ->where('site_id', $site->id)
        ->whereRelation('type', 'key', BlogPageTypeEnum::Article->value)
        ->get();

    expect($articles->count())->toBeGreaterThanOrEqual(1);

    $tagModel = CapellCore::getModel(BlogModelEnum::Tag);
    $tagsCount = $tagModel::query()->count();
    expect($tagsCount)->toBeGreaterThanOrEqual(1);

    $articleWithTags = $articles->first(fn (Pageable $article): bool => $article->tags()->exists());

    expect($articleWithTags)->not()->toBeNull();
});
