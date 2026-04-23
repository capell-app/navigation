<?php

declare(strict_types=1);

use Capell\SeoTools\Assistant\Contracts\ContentTargetContract;
use Capell\SeoTools\Assistant\Targets\FlatJsonTarget;

it('reports its handle key', function (): void {
    expect((new FlatJsonTarget)->handles())->toBe('flat_json');
});

it('implements ContentTargetContract', function (): void {
    expect(new FlatJsonTarget)
        ->toBeInstanceOf(ContentTargetContract::class);
});
