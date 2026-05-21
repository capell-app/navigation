<?php

declare(strict_types=1);

use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Policies\NavigationPolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User;

it('authorizes navigation resource abilities through shield permissions', function (): void {
    $policy = new NavigationPolicy;
    $navigation = new Navigation;
    $user = new class extends User
    {
        use HasFactory;

        /** @var list<string> */
        public array $permissions = [];

        public function checkPermissionTo(mixed $permission, mixed $guardName = null): bool
        {
            return in_array((string) $permission, $this->permissions, true);
        }
    };

    expect($policy->viewAny($user))->toBeFalse()
        ->and($policy->view($user, $navigation))->toBeFalse();

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
    ];

    expect($policy->create($user))->toBeTrue()
        ->and($policy->update($user, $navigation))->toBeTrue()
        ->and($policy->delete($user, $navigation))->toBeTrue()
        ->and($policy->deleteAny($user))->toBeTrue()
        ->and($policy->restore($user, $navigation))->toBeTrue()
        ->and($policy->forceDelete($user, $navigation))->toBeTrue()
        ->and($policy->reorder($user))->toBeTrue();
});
