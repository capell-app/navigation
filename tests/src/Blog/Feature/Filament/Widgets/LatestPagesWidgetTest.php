<?php

declare(strict_types=1);

use Capell\Admin\Filament\Widgets\LatestPagesWidget;
use Capell\Blog\Models\Article;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('page');

it('renders the pages widget', function (): void {
    test()->actingAsAdmin();

    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations()->create();

    Page::factory(5)->site($site)->withTranslations()->create();

    Article::factory()->site($site)->withTranslations()->count(5)->create();

    livewire(LatestPagesWidget::class)
        ->assertOk()
        ->assertCountTableRecords(10);
});
