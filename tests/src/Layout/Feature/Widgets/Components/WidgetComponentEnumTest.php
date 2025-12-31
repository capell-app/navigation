<?php

declare(strict_types=1);

use Capell\Layout\Enums\WidgetComponentEnum;

it('has component enum cases with valid mappings', function (): void {
    $components = WidgetComponentEnum::getComponents();

    foreach (WidgetComponentEnum::cases() as $case) {
        expect($case->value)->toBeString();

        // ensure getComponents exposes the case key
        expect(array_key_exists($case->value, $components))->toBeTrue();

        // the mapping may be a class-string or null for blade-only components
        $mapped = $components[$case->value];
        expect(is_string($mapped) || $mapped === null)->toBeTrue();
    }
});
