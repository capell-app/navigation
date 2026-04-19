<?php

declare(strict_types=1);

use Capell\Themes\Core\SEO\StructuredDataBuilder;

test('organization schema renders valid JSON-LD with @type Organization', function (): void {
    $builder = new StructuredDataBuilder;
    $builder->organization('Acme Corp', 'https://example.com');

    $schemas = $builder->toArray();

    expect($schemas)->toHaveCount(1);
    expect($schemas[0]['@type'])->toBe('Organization');
    expect($schemas[0]['name'])->toBe('Acme Corp');
    expect($schemas[0]['url'])->toBe('https://example.com');
    expect($schemas[0]['@context'])->toBe('https://schema.org');
});

test('BreadcrumbList renders correctly with ItemList structure', function (): void {
    $builder = new StructuredDataBuilder;
    $builder->breadcrumbList([
        ['name' => 'Home', 'url' => 'https://example.com'],
        ['name' => 'About', 'url' => 'https://example.com/about'],
    ]);

    $schemas = $builder->toArray();

    expect($schemas[0]['@type'])->toBe('BreadcrumbList');
    expect($schemas[0]['itemListElement'])->toHaveCount(2);
    expect($schemas[0]['itemListElement'][0]['@type'])->toBe('ListItem');
    expect($schemas[0]['itemListElement'][0]['position'])->toBe(1);
    expect($schemas[0]['itemListElement'][0]['name'])->toBe('Home');
    expect($schemas[0]['itemListElement'][1]['position'])->toBe(2);
});

test('FAQPage renders with Question and Answer pairs', function (): void {
    $builder = new StructuredDataBuilder;
    $builder->faqPage([
        ['question' => 'What is Capell?', 'answer' => 'A CMS platform.'],
        ['question' => 'Is it free?', 'answer' => 'Yes.'],
    ]);

    $schemas = $builder->toArray();

    expect($schemas[0]['@type'])->toBe('FAQPage');
    expect($schemas[0]['mainEntity'])->toHaveCount(2);
    expect($schemas[0]['mainEntity'][0]['@type'])->toBe('Question');
    expect($schemas[0]['mainEntity'][0]['name'])->toBe('What is Capell?');
    expect($schemas[0]['mainEntity'][0]['acceptedAnswer']['@type'])->toBe('Answer');
    expect($schemas[0]['mainEntity'][0]['acceptedAnswer']['text'])->toBe('A CMS platform.');
});

test('article schema includes all expected fields', function (): void {
    $builder = new StructuredDataBuilder;
    $builder->article(
        headline: 'Hello World',
        description: 'A blog post',
        url: 'https://example.com/blog/hello',
        datePublished: '2024-01-01',
        author: 'Jane Doe',
    );

    $schemas = $builder->toArray();

    expect($schemas[0]['@type'])->toBe('Article');
    expect($schemas[0]['headline'])->toBe('Hello World');
    expect($schemas[0]['description'])->toBe('A blog post');
    expect($schemas[0]['url'])->toBe('https://example.com/blog/hello');
    expect($schemas[0]['datePublished'])->toBe('2024-01-01');
    expect($schemas[0]['author']['name'])->toBe('Jane Doe');
});

test('render() wraps each schema in its own script tag', function (): void {
    $builder = new StructuredDataBuilder;
    $builder->organization('Acme', 'https://example.com');
    $builder->webPage('Home', 'Welcome', 'https://example.com');

    $rendered = $builder->render();

    expect(substr_count($rendered, '<script type="application/ld+json">'))->toBe(2);
    expect($rendered)->toContain('Organization');
    expect($rendered)->toContain('WebPage');
});

test('toArray() returns the raw schemas array', function (): void {
    $builder = new StructuredDataBuilder;

    expect($builder->toArray())->toBe([]);

    $builder->webPage('About', 'About us', 'https://example.com/about');

    $schemas = $builder->toArray();
    expect($schemas)->toHaveCount(1);
    expect($schemas[0])->toBeArray();
});
