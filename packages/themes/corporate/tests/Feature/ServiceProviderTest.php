<?php

declare(strict_types=1);

use Capell\Themes\Corporate\CorporateThemeServiceProvider;

test('provider exposes widget list', function (): void {
    $widgets = CorporateThemeServiceProvider::widgets();

    expect($widgets)->toBeArray()->toHaveCount(7);
    foreach ($widgets as $class) {
        expect(class_exists($class))->toBeTrue();
    }
});

test('theme key constant is corporate', function (): void {
    expect(CorporateThemeServiceProvider::THEME_KEY)->toBe('corporate');
});
