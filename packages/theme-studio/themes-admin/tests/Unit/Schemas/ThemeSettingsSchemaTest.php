<?php

declare(strict_types=1);

use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\Themes\Admin\Rules\SafeCssColor;
use Capell\Themes\Admin\Schemas\ThemeSettingsSchema;
use Capell\Themes\Core\Theme\ThemeRegistrar;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

beforeEach(function (): void {
    ThemeRegistrar::reset();
    ThemeRegistrar::register('corporate', 'Corporate');
    ThemeRegistrar::register('agency', 'Agency');
    ThemeRegistrar::register('saas', 'SaaS');
});

/**
 * Retrieve child components directly from the internal property, avoiding
 * the need for a Filament container (which requires the full Laravel app).
 */
function getThemeChildComponents(object $component): array
{
    $reflection = new ReflectionObject($component);

    if (! $reflection->hasProperty('childComponents')) {
        return [];
    }

    $prop = $reflection->getProperty('childComponents');
    $rawValue = $prop->getValue($component);

    if (! is_array($rawValue)) {
        return [];
    }

    $components = [];

    foreach ($rawValue as $group) {
        foreach ((array) $group as $child) {
            if (is_object($child)) {
                $components[] = $child;
            }
        }
    }

    return $components;
}

function flattenThemeComponents(object $component): array
{
    $result = [$component];

    foreach (getThemeChildComponents($component) as $child) {
        $result = array_merge($result, flattenThemeComponents($child));
    }

    return $result;
}

test('ThemeSettingsSchema is registry compatible', function (): void {
    $components = ThemeSettingsSchema::make(Schema::make());

    expect(ThemeSettingsSchema::class)->toImplement(HasSchema::class)
        ->and($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(Tabs::class);
});

test('schema contains an active_theme Select', function (): void {
    $configurator = ThemeSettingsSchema::tabs();
    $components = flattenThemeComponents($configurator);

    $matches = array_filter(
        $components,
        fn (object $component): bool => $component instanceof Select && $component->getName() === 'active_theme',
    );

    expect($matches)->toHaveCount(1);
});

test('schema includes at least two ColorPickers', function (): void {
    $configurator = ThemeSettingsSchema::tabs();
    $components = flattenThemeComponents($configurator);

    $pickers = array_filter(
        $components,
        fn (object $component): bool => $component instanceof ColorPicker,
    );

    expect(count($pickers))->toBeGreaterThanOrEqual(2);
});

test('color fields include safe css color validation', function (): void {
    $configurator = ThemeSettingsSchema::tabs();
    $components = flattenThemeComponents($configurator);

    $pickers = array_filter(
        $components,
        fn (object $component): bool => $component instanceof ColorPicker,
    );

    foreach ($pickers as $picker) {
        expect($picker->getValidationRules())
            ->toContainEqual(new SafeCssColor);
    }
});

test('safe css color validation accepts hex colors and css tokens', function (string $value): void {
    $failed = false;

    (new SafeCssColor)->validate('primary_color', $value, function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse();
})->with([
    '#fff',
    '#1a2d6d',
    '#1a2d6dff',
    'var(--color-primary)',
    '--color-primary',
    'currentColor',
    'transparent',
]);

test('safe css color validation rejects unsafe css values', function (string $value): void {
    $failed = false;

    (new SafeCssColor)->validate('primary_color', $value, function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeTrue();
})->with([
    'url(https://example.com/color.svg)',
    'expression(alert(1))',
    '#12',
    '#12345g',
    'var(--color-primary, red)',
]);
