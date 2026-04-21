<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Mosaic\Enums\ModelEnum;
use Capell\Mosaic\Filament\Widgets\LayoutHealthWidget;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Models\WidgetAsset;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Role;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    Role::findOrCreate(config('capell.roles.developer', 'developer'));
});

it('renders for a developer user', function (): void {
    test()->actingAsRole(config('capell.roles.developer', 'developer'));

    livewire(LayoutHealthWidget::class)->assertOk();
});

it('shows a widget with no assets in unused widgets section', function (): void {
    test()->actingAsRole(config('capell.roles.developer', 'developer'));

    /** @var class-string<Widget> $widgetModel */
    $widgetModel = CapellCore::getModel(ModelEnum::Widget);
    $unusedWidget = $widgetModel::factory()->create(['name' => 'My Unused Widget']);

    livewire(LayoutHealthWidget::class)
        ->assertOk()
        ->assertSee('Unused Widget Types')
        ->assertSee('My Unused Widget');
});

it('does not show unused widgets section when all widgets are in use', function (): void {
    test()->actingAsRole(config('capell.roles.developer', 'developer'));

    /** @var class-string<Widget> $widgetModel */
    $widgetModel = CapellCore::getModel(ModelEnum::Widget);
    $usedWidget = $widgetModel::factory()->create(['name' => 'Active Widget']);
    WidgetAsset::factory()->widget($usedWidget)->create();

    livewire(LayoutHealthWidget::class)
        ->assertOk()
        ->assertDontSee('Unused Widget Types');
});

it('shows only unused widgets in the unused widgets section', function (): void {
    test()->actingAsRole(config('capell.roles.developer', 'developer'));

    /** @var class-string<Widget> $widgetModel */
    $widgetModel = CapellCore::getModel(ModelEnum::Widget);
    $unusedWidget = $widgetModel::factory()->create(['name' => 'Orphan Widget']);
    $usedWidget = $widgetModel::factory()->create(['name' => 'Used Widget']);
    WidgetAsset::factory()->widget($usedWidget)->create();

    livewire(LayoutHealthWidget::class)
        ->assertOk()
        ->assertSee('Unused Widget Types')
        ->assertSeeInOrder(['Unused Widget Types', 'Orphan Widget']);
});
