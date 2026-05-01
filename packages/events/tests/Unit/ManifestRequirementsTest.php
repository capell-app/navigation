<?php

declare(strict_types=1);

use Illuminate\Support\Arr;

it('declares the events package metadata', function (): void {
    $manifest = json_decode((string) file_get_contents(__DIR__ . '/../../capell.json'), true);

    expect($manifest['name'])->toBe('capell-app/events')
        ->and($manifest['kind'])->toBe('package')
        ->and($manifest['contexts'])->toBe(['admin', 'frontend', 'console'])
        ->and($manifest['providers']['shared'])->toContain('Capell\\Events\\Providers\\EventsServiceProvider')
        ->and($manifest['providers']['admin'])->toContain('Capell\\Events\\Providers\\AdminServiceProvider')
        ->and($manifest['providers']['frontend'])->toContain('Capell\\Events\\Providers\\FrontendServiceProvider')
        ->and($manifest['providers']['console'])->toContain('Capell\\Events\\Providers\\ConsoleServiceProvider')
        ->and(Arr::get($manifest, 'commands.install'))->toBe('capell:events-install')
        ->and($manifest['requires'])->toContain('capell-app/mosaic');
});
