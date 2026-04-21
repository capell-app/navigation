<?php

declare(strict_types=1);

use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Mosaic\Filament\Widgets\RecentActivityWidget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('widget');

it('renders for an admin user', function (): void {
    test()->actingAsAdmin();
    livewire(RecentActivityWidget::class)->assertOk();
});

it('shows recent activity heading', function (): void {
    test()->actingAsAdmin();
    livewire(RecentActivityWidget::class)
        ->assertOk()
        ->assertSee('Recent activity');
});

it('shows empty state when no pages exist', function (): void {
    test()->actingAsAdmin();

    /** @var class-string<Page> $pageModel */
    $pageModel = CapellCore::getModel(ModelEnum::Page);
    $pageModel::query()->delete();

    livewire(RecentActivityWidget::class)
        ->assertOk()
        ->assertSee('No recent activity.');
});

it('shows draft status when visible_from is null', function (): void {
    test()->actingAsAdmin();

    /** @var class-string<Page> $pageModel */
    $pageModel = CapellCore::getModel(ModelEnum::Page);
    $pageModel::factory()->withTranslations()->create([
        'name' => 'Draft Page',
        'visible_from' => null,
        'visible_until' => null,
    ]);

    livewire(RecentActivityWidget::class)
        ->assertOk()
        ->assertSee('draft');
});

it('shows published status when visible_from is in the past and no visible_until', function (): void {
    test()->actingAsAdmin();

    /** @var class-string<Page> $pageModel */
    $pageModel = CapellCore::getModel(ModelEnum::Page);
    $pageModel::factory()->withTranslations()->create([
        'name' => 'Published Page',
        'visible_from' => now()->subDay(),
        'visible_until' => null,
    ]);

    livewire(RecentActivityWidget::class)
        ->assertOk()
        ->assertSee('published');
});

it('shows expired status when visible_until is in the past', function (): void {
    test()->actingAsAdmin();

    /** @var class-string<Page> $pageModel */
    $pageModel = CapellCore::getModel(ModelEnum::Page);
    $pageModel::factory()->withTranslations()->create([
        'name' => 'Expired Page',
        'visible_from' => now()->subMonth(),
        'visible_until' => now()->subDay(),
    ]);

    livewire(RecentActivityWidget::class)
        ->assertOk()
        ->assertSee('expired');
});
