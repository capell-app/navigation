<?php

declare(strict_types=1);

use Capell\Blog\Filament\Widgets\TopPagesWidget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('widget');

it('renders for an admin user', function (): void {
    test()->actingAsAdmin();
    livewire(TopPagesWidget::class)->assertOk();
});

it('shows top pages heading', function (): void {
    test()->actingAsAdmin();
    livewire(TopPagesWidget::class)
        ->assertOk()
        ->assertSee('Top pages');
});
