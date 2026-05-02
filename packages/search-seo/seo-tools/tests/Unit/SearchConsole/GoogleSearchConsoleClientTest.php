<?php

declare(strict_types=1);

use Capell\SeoTools\Enums\SearchConsoleMetricEnum;
use Capell\SeoTools\Support\SearchConsole\GoogleSearchConsoleClient;
use Illuminate\Support\Facades\Http;

it('maps search analytics rows into page insights', function (): void {
    $credentialsPath = tempnam(sys_get_temp_dir(), 'search-console-credentials');
    $privateKey = openssl_pkey_new([
        'private_key_bits' => 1024,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    $privateKeyContents = '';

    expect($credentialsPath)->toBeString();
    expect($privateKey)->not()->toBeFalse();

    openssl_pkey_export($privateKey, $privateKeyContents);

    file_put_contents($credentialsPath, json_encode([
        'client_email' => 'seo-tools@example.iam.gserviceaccount.com',
        'private_key' => $privateKeyContents,
        'token_uri' => 'https://oauth2.googleapis.com/token',
    ], JSON_THROW_ON_ERROR));

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-token'], 200),
        'https://searchconsole.googleapis.com/*' => Http::response([
            'rows' => [[
                'clicks' => 12,
                'impressions' => 120,
                'ctr' => 0.1,
                'position' => 4.2,
            ]],
        ], 200),
    ]);

    $client = new GoogleSearchConsoleClient([
        'enabled' => true,
        'credentials_path' => $credentialsPath,
        'property_url' => 'https://example.com/',
    ]);

    $insights = $client->pageInsights('https://example.com/about');

    unlink($credentialsPath);

    expect($insights)->toHaveCount(4)
        ->and($insights[0]->metric)->toBe(SearchConsoleMetricEnum::Clicks)
        ->and($insights[0]->value)->toBe(12)
        ->and($insights[1]->metric)->toBe(SearchConsoleMetricEnum::Impressions)
        ->and($insights[1]->value)->toBe(120)
        ->and($insights[2]->metric)->toBe(SearchConsoleMetricEnum::Ctr)
        ->and($insights[2]->value)->toBe(0.1)
        ->and($insights[3]->metric)->toBe(SearchConsoleMetricEnum::Position)
        ->and($insights[3]->value)->toBe(4.2);
});
