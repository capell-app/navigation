<?php

declare(strict_types=1);

use Capell\SeoTools\Assistant\Filament\Widgets\AiUsageWidget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('widget');

it('renders for an authenticated user', function (): void {
    test()->actingAsUser();

    livewire(AiUsageWidget::class)->assertOk();
});
