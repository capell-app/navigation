<?php

declare(strict_types=1);

use Capell\Assistant\Support\PromptRepository;

uses()->group('admin-ai');

it('returns prompts from config', function (): void {
    $repo = resolve(PromptRepository::class);
    $prompt = $repo->get('title_generation');
    expect($prompt)->not()->toBeNull();
    expect($prompt)->toHaveKey('system');
    expect($prompt)->toHaveKey('user_template');
});
