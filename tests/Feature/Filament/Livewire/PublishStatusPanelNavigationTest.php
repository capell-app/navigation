<?php

declare(strict_types=1);

use Capell\Admin\Filament\Livewire\PublishStatusPanel;
use Capell\Navigation\Models\Navigation;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(CreatesAdminUser::class)->group('navigation');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

function navigationPublishPanel(Navigation $navigation): Testable
{
    return Livewire::test(PublishStatusPanel::class, [
        'recordClass' => Navigation::class,
        'recordId' => $navigation->getKey(),
    ]);
}

it('shows the publish controls for a live navigation', function (): void {
    $navigation = Navigation::factory()->create([
        'visible_from' => now()->subDay(),
        'visible_until' => null,
    ]);

    navigationPublishPanel($navigation)
        ->assertOk()
        ->assertActionVisible('unpublish');
});

it('does not show a status toggle for a navigation', function (): void {
    $navigation = Navigation::factory()->create([
        'visible_from' => now()->subDay(),
    ]);

    navigationPublishPanel($navigation)
        ->assertOk()
        ->assertActionHidden('toggleStatus');
});

it('publishes a draft navigation immediately via the panel', function (): void {
    $navigation = Navigation::factory()->create([
        'visible_from' => now()->addYears(100),
        'visible_until' => null,
    ]);

    navigationPublishPanel($navigation)->callAction('publishNow');

    expect($navigation->fresh()->isPending())->toBeFalse();
});

it('unpublishes a live navigation via the panel', function (): void {
    $navigation = Navigation::factory()->create([
        'visible_from' => now()->subDay(),
        'visible_until' => null,
    ]);

    navigationPublishPanel($navigation)->callAction('unpublish');

    expect($navigation->fresh()->isExpired())->toBeTrue();
});
