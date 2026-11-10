<?php

declare(strict_types=1);

use Capell\Navigation\Support\OptionalBladeComponent;

it('resolves the foundation dropdown through the optional component lookup', function (): void {
    expect(resolve(OptionalBladeComponent::class)->resolves('capell::dropdown'))->toBeTrue()
        ->and(resolve(OptionalBladeComponent::class)->resolves('capell::no-such-component'))->toBeFalse();
});
