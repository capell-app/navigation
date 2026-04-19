<?php

declare(strict_types=1);

use Capell\Themes\Core\SEO\AbstractThemeSchemaGenerator;

$makeGenerator = function (): AbstractThemeSchemaGenerator {
    return new class extends AbstractThemeSchemaGenerator
    {
        protected function resolveOrgName(): string
        {
            return 'Stub Corp';
        }

        /** @return array<int, string> */
        protected function resolveSameAs(): array
        {
            return ['https://twitter.com/stub'];
        }
    };
};

test('organization() includes name, url, and sameAs', function () use ($makeGenerator): void {
    $result = $makeGenerator()->organization('https://example.com');
    expect($result['@type'])->toBe('Organization');
    expect($result['name'])->toBe('Stub Corp');
    expect($result['url'])->toBe('https://example.com');
    expect($result['sameAs'])->toContain('https://twitter.com/stub');
});

test('website() includes potentialAction search', function () use ($makeGenerator): void {
    $result = $makeGenerator()->website('https://example.com', 'Stub');
    expect($result['@type'])->toBe('WebSite');
    expect($result['potentialAction']['@type'])->toBe('SearchAction');
});

test('breadcrumb() numbers items from 1', function () use ($makeGenerator): void {
    $result = $makeGenerator()->breadcrumb([
        ['name' => 'Home', 'url' => '/'],
        ['name' => 'Blog', 'url' => '/blog'],
    ]);
    expect($result['@type'])->toBe('BreadcrumbList');
    expect($result['itemListElement'][0]['position'])->toBe(1);
    expect($result['itemListElement'][1]['position'])->toBe(2);
});

test('faq() wraps items as Question/Answer pairs', function () use ($makeGenerator): void {
    $result = $makeGenerator()->faq([['question' => 'Q?', 'answer' => 'A.']]);
    expect($result['@type'])->toBe('FAQPage');
    expect($result['mainEntity'][0]['@type'])->toBe('Question');
});

test('toJsonLd() produces valid JSON with unescaped slashes', function () use ($makeGenerator): void {
    $json = $makeGenerator()->toJsonLd(['url' => 'https://example.com/page']);
    expect($json)->toContain('https://example.com/page');
    expect($json)->not->toContain('https:\/\/');
});
