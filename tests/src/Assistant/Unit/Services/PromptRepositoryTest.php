<?php

declare(strict_types=1);

use Capell\Assistant\Data\PromptData;

uses()->group('admin-ai');

it('maps settings prompts to typed prompt data', function (): void {
    $prompts = PromptData::from([
        'model' => 'gpt-4-turbo',
        'title_generation' => true,
        'title_generation_system' => 'title-system',
        'title_generation_user_template' => 'title-template',
        'meta_description' => true,
        'meta_description_system' => 'meta-system',
        'meta_description_user_template' => 'meta-template',
        'content_generation' => true,
        'content_generation_system' => 'content-system',
        'content_generation_user_template' => 'content-template',
    ]);

    expect($prompts)->titleGeneration->toBeTrue()
        ->model->toBe('gpt-4-turbo')
        ->and($prompts->titleGenerationSystem)->toBe('title-system')
        ->and($prompts->titleGenerationUserTemplate)->toBe('title-template')
        ->and($prompts->metaDescription)->toBeTrue()
        ->and($prompts->metaDescriptionSystem)->toBe('meta-system')
        ->and($prompts->metaDescriptionUserTemplate)->toBe('meta-template')
        ->and($prompts->contentGeneration)->toBeTrue()
        ->and($prompts->contentGenerationSystem)->toBe('content-system')
        ->and($prompts->contentGenerationUserTemplate)->toBe('content-template');
});
