<?php

declare(strict_types=1);

it('runs clear AI cache command successfully', function (): void {
    $this->artisan('capell:admin-clear-ai-cache')
        ->assertExitCode(0);
});
