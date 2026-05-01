<?php

declare(strict_types=1);

use Capell\Core\Models\BrokenLink;
use Capell\Core\Models\Page;
use Capell\Redirects\Support\DatabaseBrokenUrlReporter;

it('records a broken url for a page', function (): void {
    $page = Page::factory()->create();
    $reporter = new DatabaseBrokenUrlReporter;

    $reporter->report('https://example.com/missing', 404, $page->getKey());

    /** @var BrokenLink $brokenLink */
    $brokenLink = BrokenLink::query()->where('page_id', $page->getKey())->firstOrFail();

    expect($brokenLink->target_url)->toBe('https://example.com/missing')
        ->and($brokenLink->http_status)->toBe(404)
        ->and($brokenLink->last_checked_at)->not()->toBeNull();
});

it('updates an existing broken url report', function (): void {
    $page = Page::factory()->create();
    BrokenLink::query()->create([
        'page_id' => $page->getKey(),
        'target_url' => 'https://example.com/missing',
        'http_status' => 404,
    ]);
    $reporter = new DatabaseBrokenUrlReporter;

    $reporter->report('https://example.com/missing', 500, $page->getKey());

    /** @var BrokenLink $brokenLink */
    $brokenLink = BrokenLink::query()->where('page_id', $page->getKey())->firstOrFail();

    expect(BrokenLink::query()->where('page_id', $page->getKey())->count())->toBe(1)
        ->and($brokenLink->http_status)->toBe(500);
});

it('ignores reports when broken url tracking is disabled', function (): void {
    config()->set('redirects.broken_urls.enabled', false);
    $page = Page::factory()->create();
    $reporter = new DatabaseBrokenUrlReporter;

    $reporter->report('https://example.com/missing', 404, $page->getKey());

    expect(BrokenLink::query()->count())->toBe(0);
});

it('ignores reports without page context', function (): void {
    $reporter = new DatabaseBrokenUrlReporter;

    $reporter->report('https://example.com/missing', 404);

    expect(BrokenLink::query()->count())->toBe(0);
});
