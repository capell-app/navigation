<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Blog\Database\Factories\ArticleFactory;
use Capell\Blog\Filament\Resources\Articles\ArticleResource;
use Capell\Blog\Filament\Resources\Articles\Pages\EditArticle;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageTranslation;
use Capell\Core\Models\Site;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('page', 'article');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can render article', function (): void {
    get(ArticleResource::getUrl('edit', [
        'record' => (new ArticleFactory)->create(),
    ]))->assertSuccessful();
});

test('can not render article', function (): void {
    test()->withoutExceptionHandling();

    get(PageResource::getUrl('edit', [
        'record' => (new ArticleFactory)->create(),
    ]));
})->throws(ModelNotFoundException::class);

test('can not render page', function (): void {
    test()->withoutExceptionHandling();

    get(ArticleResource::getUrl('edit', [
        'record' => Page::factory()->create(),
    ]));
})->throws(ModelNotFoundException::class);

it('can save', function (): void {
    $site = Site::factory()->hasSiteDomains()->create();
    $languages = $site->siteDomains->map->language_id;

    $page = (new ArticleFactory)->recycle($site)->create();

    $languages->each(function (int $languageId) use ($page): void {
        $page->translations()->save(PageTranslation::factory()->make([
            'language_id' => $languageId,
            'title' => Str::title($page->name . ' ' . $languageId),
            'slug' => Str::slug($page->name . ' ' . $languageId),
        ]));
    });

    $page->refresh();

    $newData = (new ArticleFactory)->site($site)->make();

    livewire(EditArticle::class, [
        'record' => $page->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSchemaStateSet([
            'name' => $page->name,
            'layout_id' => $page->layout->getKey(),
            'type_id' => $page->type->getKey(),
            'site_id' => $page->site->getKey(),
        ])
        ->fillForm([
            'name' => $newData->name,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($page->refresh())
        ->name->toBe($newData->name);
});

it('can delete', function (): void {
    $content = (new ArticleFactory)->create();

    livewire(EditArticle::class, [
        'record' => $content->getRouteKey(),
    ])
        ->assertSuccessful()
        ->callAction(DeleteAction::class)
        ->assertHasNoFormErrors();

    assertSoftDeleted($content, ['id' => $content->id]);
});
