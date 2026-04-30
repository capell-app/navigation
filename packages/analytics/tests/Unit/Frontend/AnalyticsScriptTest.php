<?php

declare(strict_types=1);

it('contains the browser tracking primitives', function (): void {
    $source = file_get_contents(__DIR__ . '/../../../resources/js/capell-analytics.js');

    expect($source)
        ->toContain('navigator.sendBeacon')
        ->toContain('keepalive: true')
        ->toContain('data-capell-analytics-ignore')
        ->toContain('data-capell-analytics-label')
        ->toContain('data-capell-analytics-location');
});
