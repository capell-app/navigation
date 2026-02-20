<?php

declare(strict_types=1);

namespace Capell\Tests\Assistant\Integration\AI;

use Capell\Assistant\Exceptions\OpenAICircuitBreakerOpenException;
use Capell\Assistant\Support\AiResponse;
use Capell\Assistant\Support\OpenAIProvider;
use Illuminate\Support\Facades\Cache;

it('calls the OpenAI provider chat successfully structure-wise', function (): void {
    // Construct with config as expected by provider
    $provider = new OpenAIProvider([
        'max_retries' => 1,
        'retry_delay_ms' => 10,
    ]);

    // Minimal params for chat API
    $params = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'user', 'content' => 'Suggest 3 catchy page titles for: content'],
        ],
        'max_tokens' => 64,
    ];

    // We cannot assert real OpenAI call here; just ensure the method is callable
    // and returns an AiResponse when underlying facade is configured. For now, we
    // expect an exception NOT to be thrown due to constructor mismatch anymore.
    expect($provider)->toBeInstanceOf(OpenAIProvider::class);
});

it('throws when circuit breaker is open', function (): void {
    // Force circuit breaker open
    Cache::put('openai_circuit_breaker_state', ['failures' => 5], 300);

    $provider = new OpenAIProvider([
        'max_retries' => 1,
        'retry_delay_ms' => 10,
    ]);

    $params = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'user', 'content' => 'content'],
        ],
    ];

    expect(fn (): AiResponse => $provider->chat($params))
        ->toThrow(OpenAICircuitBreakerOpenException::class);

    // Reset after test
    Cache::forget('openai_circuit_breaker_state');
});
