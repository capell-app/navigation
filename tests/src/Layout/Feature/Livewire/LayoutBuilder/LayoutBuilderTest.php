<?php

declare(strict_types=1);

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Support\Creator\LayoutCreator;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Database\Factories\WidgetTypeFactory;
use Capell\Layout\Enums\AssetEnum;
use Capell\Layout\Enums\LivewireComponentsEnum;
use Capell\Layout\Filament\Resources\Layouts\Schemas\Types\Widgets\DefaultLayoutWidgetSchema;
use Capell\Layout\Livewire\Filament\LayoutBuilder;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Capell\Layout\Support\Creator\TypeCreator;
use Capell\Layout\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Testing\TestAction;
use Pest\Expectation;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('pages');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

// ──────────────────────────────────────────────
// Rendering
// ──────────────────────────────────────────────

test('it renders the layout builder with containers', function (): void {
    $layout = (new LayoutFactory)->containers()->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->assertSeeText(__('capell-layout::heading.layout_record', ['name' => $layout->name]));
});

test('it renders the layout builder for a page', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    livewire(LayoutBuilder::class, [
        'layout' => $layout,
        'page' => $page,
    ])
        ->assertSuccessful()
        ->assertSeeText(__('capell-layout::heading.layout_record', ['name' => $layout->name]));
});

test('it renders an empty layout message when no containers exist', function (): void {
    $layout = (new LayoutFactory)->state(['containers' => []])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->assertSeeHtml(__('capell-layout::message.layout_empty'));
});

test('it renders for each layout enum type', function (LayoutEnum $layoutEnum): void {
    $language = Language::factory()->create();

    $layout = resolve(LayoutCreator::class)->create($layoutEnum->value);

    resolve(TypeCreator::class)->createWidgetTypes();
    resolve(WidgetCreator::class)->createWidgets(collect([$language]));

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->assertSeeText(__('capell-layout::heading.layout_record', ['name' => $layout->name]));
})->with(LayoutEnum::cases());

test('it renders widgets with asset types', function (AssetEnum|Capell\Core\Enums\AssetEnum $assetType): void {
    $widget = Widget::factory()
        ->for((new WidgetTypeFactory)->state([
            'admin' => [
                'asset_types' => [$assetType],
            ],
        ]))
        ->has(WidgetAsset::factory()->asset($assetType), 'assets')
        ->create();

    $layout = (new LayoutFactory)->state([
        'containers' => ['main' => ['widgets' => [['widget_key' => $widget->key]]]],
    ])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful();
})->with([AssetEnum::Content, ...Capell\Core\Enums\AssetEnum::cases()]);

// ──────────────────────────────────────────────
// Container operations
// ──────────────────────────────────────────────

test('it adds a container with metadata and persists it', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $originalCount = count($layout->containers);

    $containerKey = array_key_first($layout->containers) . '-2';
    $htmlClass = 'test-class';

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->callAction(
            'addContainer',
            arguments: ['containerKey' => $containerKey],
            data: [
                'key' => $containerKey,
                'meta' => ['html_class' => $htmlClass],
            ],
        )
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    $layout->refresh();

    expect($layout->containers)
        ->toHaveCount($originalCount + 1)
        ->toHaveKey($containerKey)
        ->and($layout->containers[$containerKey]['meta']['html_class'])
        ->toEqual($htmlClass);
});

test('it removes a container and persists the change', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $originalCount = count($layout->containers);

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->callAction('removeContainer', arguments: [
            'containerKey' => $containerKey,
        ])
        ->call('saveLayout');

    $layout->refresh();

    expect($layout->containers)
        ->toHaveCount($originalCount - 1)
        ->not()->toHaveKey($containerKey);
});

test('it edits a container key and preserves widget assets', function (): void {
    $layout = (new LayoutFactory)->containers()->create();

    $containerKey = array_key_first($layout->containers);
    $newContainerKey = $containerKey . '-renamed';
    $containerWidget = $layout->containers[$containerKey]['widgets'][0];
    $widget = Widget::query()->firstWhere('key', $containerWidget['widget_key']);

    WidgetAsset::factory()
        ->count(2)
        ->widget($widget)
        ->occurrence($containerWidget['occurrence'])
        ->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->callAction(
            'editContainer',
            data: ['key' => $newContainerKey],
            arguments: ['containerKey' => $containerKey],
        )
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    $layout->refresh();

    expect($layout->containers)
        ->toHaveKey($newContainerKey)
        ->not()->toHaveKey($containerKey);

    $assets = WidgetAsset::query()
        ->where('widget_id', $widget->id)
        ->whereNull(['pageable_type', 'pageable_id'])
        ->get();

    expect($assets)
        ->toHaveCount(2)
        ->each(
            fn (Expectation $expect) => $expect
                ->widget_id->toEqual($widget->id)
                ->container->toBeNull()
                ->page_id->toBeNull(),
        );
});

test('it edits a container key for a page layout and updates page asset references', function (string $widgetKey): void {
    $layout = (new LayoutFactory)->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    $containerKey = array_key_first($layout->containers);
    $newContainerKey = $containerKey . '-renamed';

    foreach ($layout->containers as $container) {
        foreach ($container['widgets'] as $widget) {
            if ($widget['widget_key'] === $widgetKey) {
                $containerWidget = $widget;
                break 2;
            }
        }
    }

    throw_unless(isset($containerWidget), Exception::class, sprintf('Widget with key %s not found in layout containers.', $widgetKey));

    $widget = Widget::query()->firstWhere('key', $containerWidget['widget_key']);

    WidgetAsset::factory()
        ->count(2)
        ->widget($widget)
        ->page($page, $containerKey, $containerWidget['occurrence'])
        ->create();

    livewire(LayoutBuilder::class, [
        'layout' => $layout,
        'page' => $page,
    ])
        ->assertSuccessful()
        ->callAction(
            'editContainer',
            data: ['key' => $newContainerKey],
            arguments: ['containerKey' => $containerKey],
        )
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    expect($layout->refresh()->containers)
        ->toHaveKey($newContainerKey);

    $assets = WidgetAsset::query()
        ->where([
            'pageable_type' => $page->getMorphClass(),
            'pageable_id' => $page->id,
            'widget_id' => $widget->id,
        ])
        ->get();

    expect($assets)
        ->toHaveCount(2)
        ->each(
            fn (Expectation $expect) => $expect
                ->container->toEqual($newContainerKey)
                ->widget_id->toEqual($widget->id)
                ->pageable_type->toEqual($page->getMorphClass())
                ->pageable_id->toEqual($page->id),
        );
})->with(['first', 'second']);

test('it reorders containers', function (): void {
    $widget = Widget::factory()->create(['key' => 'test']);
    $layout = (new LayoutFactory)->state([
        'containers' => [
            'first' => ['widgets' => [['widget_key' => $widget->key]]],
            'second' => ['widgets' => [['widget_key' => $widget->key]]],
        ],
    ])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->call('reorderContainers', containerKey: 'second', position: 0)
        ->call('saveLayout');

    $layout->refresh();

    expect(array_keys($layout->containers))->toBe(['second', 'first']);
});

// ──────────────────────────────────────────────
// Widget operations
// ──────────────────────────────────────────────

test('it adds a new widget to a container', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $widget = Widget::factory()->state(['key' => 'new-widget'])->create();
    $containerKey = array_key_first($layout->containers);

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->mountAction('addWidget', arguments: ['containerKey' => $containerKey])
        ->assertSeeLivewire(LivewireComponentsEnum::WidgetTableSelect->value)
        ->callMountedAction()
        ->dispatch('add-widgets-to-container', containerKey: $containerKey, widgets: [$widget->id])
        ->call('saveLayout');

    $layout->refresh();

    $lastWidgetKey = array_key_last($layout->containers[$containerKey]['widgets']);

    expect($layout->containers[$containerKey]['widgets'][$lastWidgetKey])
        ->toBeArray()
        ->toEqual(['widget_key' => $widget->key, 'occurrence' => 1]);
});

test('it increments occurrence when adding an existing widget again', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);

    $lastWidgetKey = array_key_last($layout->containers[$containerKey]['widgets']);
    $lastWidget = $layout->containers[$containerKey]['widgets'][$lastWidgetKey];
    $widget = Widget::query()->firstWhere('key', $lastWidget['widget_key']);

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->mountAction('addWidget', arguments: ['containerKey' => $containerKey])
        ->assertSeeLivewire(LivewireComponentsEnum::WidgetTableSelect->value)
        ->callMountedAction()
        ->dispatch('add-widgets-to-container', containerKey: $containerKey, widgets: [$widget->id])
        ->call('saveLayout');

    $layout->refresh();

    $lastWidgetKey = array_key_last($layout->containers[$containerKey]['widgets']);

    expect($layout->containers[$containerKey]['widgets'][$lastWidgetKey])
        ->toBeArray()
        ->toEqual([
            'widget_key' => $widget->key,
            'occurrence' => $lastWidget['occurrence'] + 1,
        ]);
});

test('it removes a widget from a container and persists the change', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $originalWidgetCount = count($layout->containers[$containerKey]['widgets']);
    $widgetIndex = array_key_last($layout->containers[$containerKey]['widgets']);

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->callAction('removeWidget', arguments: [
            'containerKey' => $containerKey,
            'widgetIndex' => $widgetIndex,
        ])
        ->call('saveLayout');

    $layout->refresh();

    expect($layout->containers[$containerKey]['widgets'])
        ->toHaveCount($originalWidgetCount - 1);
});

test('it duplicates a widget within the same container', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_last($layout->containers[$containerKey]['widgets']);
    $lastWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];
    $widget = Widget::query()->firstWhere('key', $lastWidget['widget_key']);
    $originalWidgetCount = count($layout->containers[$containerKey]['widgets']);

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->callAction('duplicateWidget', arguments: [
            'containerKey' => $containerKey,
            'widgetIndex' => $widgetIndex,
        ])
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    $layout->refresh();

    $lastWidgetKey = array_key_last($layout->containers[$containerKey]['widgets']);

    expect($layout->containers[$containerKey]['widgets'])
        ->toHaveCount($originalWidgetCount + 1)
        ->and($layout->containers[$containerKey]['widgets'][$lastWidgetKey])
        ->toBeArray()
        ->toEqual([
            'widget_key' => $widget->key,
            'occurrence' => 3,
        ]);
});

test('it edits a widget name', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_last($layout->containers[$containerKey]['widgets']);
    $lastWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::query()->firstWhere('key', $lastWidget['widget_key']);
    $newWidget = Widget::factory()->make();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->callAction(
            'editWidget',
            data: ['name' => $newWidget->name],
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
            ],
        )
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    expect($widget->refresh()->name)->toEqual($newWidget->name);
});

test('it edits layout widget meta via the editLayoutWidget action', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_last($layout->containers[$containerKey]['widgets']);

    $widget = Widget::factory()
        ->for((new WidgetTypeFactory)->state([
            'admin' => [
                'layout_widget_schema' => DefaultLayoutWidgetSchema::getKey(),
            ],
        ]), 'type')
        ->create();

    $widgetIndex++;

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->mountAction('addWidget', arguments: ['containerKey' => $containerKey])
        ->assertSeeLivewire(LivewireComponentsEnum::WidgetTableSelect->value)
        ->dispatch('add-widgets-to-container', containerKey: $containerKey, widgets: [$widget->id])
        ->callMountedAction()
        ->mountAction(
            TestAction::make('editLayoutWidget')
                ->arguments([
                    'containerKey' => $containerKey,
                    'widgetIndex' => $widgetIndex,
                ]),
        )
        ->fillForm(['html_class' => 'foo'])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    $layout->refresh();

    expect($layout->containers[$containerKey]['widgets'][$widgetIndex])
        ->toBeArray()
        ->widget_key->toEqual($widget->key)
        ->occurrence->toEqual(1)
        ->meta->toMatchArray(['html_class' => 'foo']);
});

// ──────────────────────────────────────────────
// Reordering widgets
// ──────────────────────────────────────────────

test('it reorders widgets within the same container', function (): void {
    Widget::factory()->state(['key' => 'first'])->create();
    Widget::factory()->state(['key' => 'second'])->create();

    $layout = (new LayoutFactory)->state([
        'containers' => [
            'test' => [
                'widgets' => [
                    ['widget_key' => 'first', 'occurrence' => 1],
                    ['widget_key' => 'second', 'occurrence' => 1],
                ],
            ],
        ],
    ])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->call('reorderWidgets', containerKey: 'test', containerWidgetIndex: 'test.1', widgetIndex: 0)
        ->call('saveLayout');

    $layout->refresh();

    expect(array_column($layout->containers['test']['widgets'], 'widget_key'))
        ->toBe(['second', 'first']);
});

test('it reorders a newly added widget within the same container', function (): void {
    Widget::factory()->state(['key' => 'first'])->create();
    Widget::factory()->state(['key' => 'second'])->create();
    $widget = Widget::factory()->state(['key' => 'third'])->create();

    $containerKey = 'test';

    $layout = (new LayoutFactory)->state([
        'containers' => [
            $containerKey => [
                'widgets' => [
                    ['widget_key' => 'first', 'occurrence' => 1],
                    ['widget_key' => 'second', 'occurrence' => 1],
                ],
            ],
        ],
    ])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->mountAction('addWidget', arguments: ['containerKey' => $containerKey])
        ->assertSeeLivewire(LivewireComponentsEnum::WidgetTableSelect->value)
        ->callMountedAction()
        ->dispatch('add-widgets-to-container', containerKey: $containerKey, widgets: [$widget->id])
        ->call('reorderWidgets', containerKey: $containerKey, containerWidgetIndex: $containerKey . '.2', widgetIndex: 1)
        ->call('saveLayout');

    $layout->refresh();

    expect(array_column($layout->containers[$containerKey]['widgets'], 'widget_key'))
        ->toBe(['first', 'third', 'second']);
});

// ──────────────────────────────────────────────
// Layout persistence
// ──────────────────────────────────────────────

test('it saves the layout without modifications preserving existing containers and assets', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containers = $layout->containers;

    $containerKey = array_key_first($containers);
    $containerWidget = $containers[$containerKey]['widgets'][0];
    $widget = Widget::query()->firstWhere('key', $containerWidget['widget_key']);

    WidgetAsset::factory()
        ->count(2)
        ->widget($widget)
        ->occurrence($containerWidget['occurrence'])
        ->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->set('layoutModified', true)
        ->call('saveLayout');

    expect($layout->refresh()->getAttribute('containers'))
        ->toHaveCount(count($containers))
        ->toEqual($containers);

    expect(
        WidgetAsset::query()
            ->where('widget_id', $widget->id)
            ->whereNull(['pageable_id', 'pageable_type'])
            ->exists(),
    )->toBeTrue();
});

test('it clones the layout and page association', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    livewire(LayoutBuilder::class, [
        'layout' => $layout,
        'page' => $page,
    ])
        ->assertSuccessful()
        ->callAction('duplicateLayoutAction')
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    $clonedLayout = Layout::query()
        ->where('name', $layout->name . ' (2)')
        ->where('key', $layout->key . ' (2)')
        ->first();

    expect($clonedLayout)
        ->toBeInstanceOf(Layout::class)
        ->not()->toBe($layout)
        ->and($clonedLayout->getAttribute('containers'))
        ->toEqual($layout->getAttribute('containers'));
});
