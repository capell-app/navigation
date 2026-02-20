<?php

declare(strict_types=1);

use Capell\Assistant\Support\PromptRepository;

it('retrieves prompt templates by key', function (): void {
    $repo = new PromptRepository;

    $template = $repo->get('suggest_titles');

    expect($template)->toBeNull(); // No prompts set by default
});

it('returns null for missing key', function (): void {
    $repo = new PromptRepository;

    expect($repo->get('missing'))->toBeNull();
});
