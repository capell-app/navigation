<?php

declare(strict_types=1);

namespace Capell\Tests\Assistant\Fixtures;

use Capell\Assistant\Support\AiResponse;
use Capell\Assistant\Support\OpenAIProvider;

class FakeOpenAIProvider extends OpenAIProvider
{
    public function chat(array $params): AiResponse
    {
        return new AiResponse(
            content: "- Title A\n- Title B\n- Title C",
            tokensUsed: 12,
            model: (string) ($params['model'] ?? 'test-model'),
            duration: 0.001,
            metadata: [
                'prompt_tokens' => 6,
                'completion_tokens' => 6,
                'finish_reason' => 'stop',
            ],
        );
    }
}
