<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\Pages\ListPages;
use Capell\Blog\Database\Factories\ArticleFactory;
use Capell\Core\Models\Page;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('page');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can list pages', function (): void {
    (new ArticleFactory)->create();

    $pages = Page::factory()->count(5)->create();

    livewire(ListPages::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($pages);
});
