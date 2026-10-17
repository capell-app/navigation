<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Policies\NavigationPolicy;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;

uses(CreatesAdminUser::class);

it('authorizes navigation resource abilities through shield permissions', function (): void {
    $policy = new NavigationPolicy;
    $navigation = new Navigation(['site_id' => 1]);
    $user = new class extends User
    {
        /** @use HasFactory<Factory<static>> */
        use HasFactory;

        /** @var list<string> */
        public array $permissions = [];

        /** @var Collection<int, int> */
        public Collection $assignedSiteIds;

        public function checkPermissionTo(mixed $permission, mixed $guardName = null): bool
        {
            return in_array((string) $permission, $this->permissions, true);
        }

        /** @return Collection<int, int> */
        public function getAssignedSiteIds(): Collection
        {
            return $this->assignedSiteIds;
        }

        public function isGlobalAdmin(): bool
        {
            return false;
        }
    };
    $user->assignedSiteIds = collect([1]);

    expect($policy->viewAny($user))->toBeFalse()
        ->and($policy->view($user, $navigation))->toBeFalse()
        ->and($policy->restoreAny($user))->toBeFalse()
        ->and($policy->forceDeleteAny($user))->toBeFalse();

    $user->permissions = ['View:Navigation'];

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->view($user, $navigation))->toBeTrue()
        ->and($policy->create($user))->toBeFalse();

    $user->permissions = [
        'Create:Navigation',
        'Update:Navigation',
        'Delete:Navigation',
        'DeleteAny:Navigation',
        'Restore:Navigation',
        'ForceDelete:Navigation',
        'Reorder:Navigation',
        'RestoreAny:Navigation',
        'ForceDeleteAny:Navigation',
        'Replicate:Navigation',
    ];

    expect($policy->create($user))->toBeTrue()
        ->and($policy->update($user, $navigation))->toBeTrue()
        ->and($policy->delete($user, $navigation))->toBeTrue()
        ->and($policy->deleteAny($user))->toBeTrue()
        ->and($policy->restore($user, $navigation))->toBeTrue()
        ->and($policy->forceDelete($user, $navigation))->toBeTrue()
        ->and($policy->reorder($user))->toBeTrue()
        ->and($policy->restoreAny($user))->toBeTrue()
        ->and($policy->forceDeleteAny($user))->toBeTrue()
        ->and($policy->replicate($user, $navigation))->toBeTrue();
});

it('enforces navigation site scope on direct record abilities', function (): void {
    $assignedSite = Site::factory()->create();
    $otherSite = Site::factory()->create();
    $assignedNavigation = Navigation::factory()->site($assignedSite)->create();
    $otherNavigation = Navigation::factory()->site($otherSite)->create();
    $globalNavigation = Navigation::factory()->create(['site_id' => null]);

    $policy = new NavigationPolicy;
    $user = new class extends User
    {
        /** @use HasFactory<Factory<static>> */
        use HasFactory;

        /** @var list<string> */
        public array $permissions = [
            'ViewAny:Navigation',
            'Update:Navigation',
            'Delete:Navigation',
            'Restore:Navigation',
            'ForceDelete:Navigation',
            'Replicate:Navigation',
        ];

        /** @var Collection<int, int> */
        public Collection $assignedSiteIds;

        public function checkPermissionTo(mixed $permission, mixed $guardName = null): bool
        {
            return in_array((string) $permission, $this->permissions, true);
        }

        /** @return Collection<int, int> */
        public function getAssignedSiteIds(): Collection
        {
            return $this->assignedSiteIds;
        }

        public function isGlobalAdmin(): bool
        {
            return false;
        }
    };
    $user->assignedSiteIds = collect([(int) $assignedSite->getKey()]);

    expect($policy->view($user, $assignedNavigation))->toBeTrue()
        ->and($policy->update($user, $assignedNavigation))->toBeTrue()
        ->and($policy->delete($user, $assignedNavigation))->toBeTrue()
        ->and($policy->restore($user, $assignedNavigation))->toBeTrue()
        ->and($policy->forceDelete($user, $assignedNavigation))->toBeTrue()
        ->and($policy->replicate($user, $assignedNavigation))->toBeTrue()
        ->and($policy->view($user, $globalNavigation))->toBeTrue()
        ->and($policy->update($user, $globalNavigation))->toBeFalse()
        ->and($policy->view($user, $otherNavigation))->toBeFalse()
        ->and($policy->update($user, $otherNavigation))->toBeFalse()
        ->and($policy->delete($user, $otherNavigation))->toBeFalse()
        ->and($policy->restore($user, $otherNavigation))->toBeFalse()
        ->and($policy->forceDelete($user, $otherNavigation))->toBeFalse();
});

it('allows a global admin to manage menus without generated navigation permissions', function (): void {
    config()->set('filament-shield.super_admin.define_via_gate', false);
    $this->actingAsAdmin();
    $user = $this->authenticatedUser();
    $navigation = Navigation::factory()->create();
    $policy = new NavigationPolicy;

    expect($user->isGlobalAdmin())->toBeTrue()
        ->and($user->checkPermissionTo('ViewAny:Navigation'))->toBeFalse();

    foreach (['viewAny', 'create', 'deleteAny', 'restoreAny', 'forceDeleteAny', 'reorder'] as $ability) {
        expect($policy->{$ability}($user))->toBeTrue($ability);
    }

    foreach (['view', 'update', 'delete', 'restore', 'forceDelete', 'replicate'] as $ability) {
        expect($policy->{$ability}($user, $navigation))->toBeTrue($ability);
    }
});
