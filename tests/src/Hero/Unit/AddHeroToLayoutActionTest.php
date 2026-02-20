<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Hero\Actions\AddHeroToLayoutAction;
use Capell\Hero\Actions\CreateHeroWidgetAction;
use Capell\Layout\Models\Widget;

describe('AddHeroToLayoutAction', function (): void {
    it('adds a hero container to a layout without one', function (): void {
        $layout = Layout::factory()->create(['containers' => []]);
        $widget = Widget::factory()->create();

        $mock = CreateHeroWidgetAction::mock();
        $mock->shouldReceive('handle')->once()->andReturn($widget);
        app()->instance(CreateHeroWidgetAction::class, $mock);

        AddHeroToLayoutAction::run($layout);

        $containers = $layout->refresh()->containers;
        expect($containers)
            ->toHaveKey('hero')
            ->and($containers['hero'])
            ->toHaveKey('widgets')
            ->and($containers['hero']['widgets'][0]['widget_key'])
            ->toBe($widget->key);
    });

    it('does not change containers when hero already exists (but still resolves widget)', function (): void {
        $widget = Widget::factory()->create();
        $existing = [
            'hero' => [
                'meta' => ['colspan' => 12, 'container' => 'full'],
                'widgets' => [['widget_key' => $widget->key]],
            ],
        ];
        $layout = Layout::factory()->create(['containers' => $existing]);

        $mock = CreateHeroWidgetAction::mock();
        $mock->shouldReceive('handle')->once()->andReturn($widget);
        app()->instance(CreateHeroWidgetAction::class, $mock);

        AddHeroToLayoutAction::run($layout);

        expect($layout->refresh()->containers)->toBe($existing);
    });
});
