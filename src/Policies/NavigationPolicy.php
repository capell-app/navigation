<?php

declare(strict_types=1);

namespace Capell\Navigation\Policies;

use Capell\Admin\Policies\Concerns\ResolvesShieldPermission;
use Capell\Admin\Support\SiteScope;
use Capell\Navigation\Models\Navigation;
use Illuminate\Foundation\Auth\User;
use Throwable;

/**
 * Policy for the Navigation resource.
 *
 * Navigations are site-scoped: users may only manage navigations belonging to
 * sites on which they hold an appropriate role.
 *
 * Permission names are built via Shield so they match the host app's
 * `config/filament-shield.php` (case + separator).
 */
class NavigationPolicy
{
    use ResolvesShieldPermission;

    private const string SUBJECT = 'Navigation';

    public function viewAny(User $user): bool
    {
        if ($this->hasPermission($user, self::permission('view_any', self::SUBJECT))) {
            return true;
        }

        return $this->hasPermission($user, self::permission('view', self::SUBJECT));
    }

    public function view(User $user, Navigation $navigation): bool
    {
        return (
            $this->hasPermission($user, self::permission('view_any', self::SUBJECT))
            || $this->hasPermission($user, self::permission('view', self::SUBJECT))
        )
            && $this->canUseNavigationSite($user, $navigation);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, self::permission('create', self::SUBJECT));
    }

    public function update(User $user, Navigation $navigation): bool
    {
        return $this->hasPermission($user, self::permission('update', self::SUBJECT))
            && $this->canMutateNavigationSite($user, $navigation);
    }

    public function delete(User $user, Navigation $navigation): bool
    {
        return $this->hasPermission($user, self::permission('delete', self::SUBJECT))
            && $this->canMutateNavigationSite($user, $navigation);
    }

    public function deleteAny(User $user): bool
    {
        return $this->hasPermission($user, self::permission('delete_any', self::SUBJECT));
    }

    public function restore(User $user, Navigation $navigation): bool
    {
        return $this->hasPermission($user, self::permission('restore', self::SUBJECT))
            && $this->canMutateNavigationSite($user, $navigation);
    }

    public function restoreAny(User $user): bool
    {
        return $this->hasPermission($user, self::permission('restore_any', self::SUBJECT));
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->hasPermission($user, self::permission('force_delete_any', self::SUBJECT));
    }

    public function replicate(User $user, Navigation $navigation): bool
    {
        return $this->hasPermission($user, self::permission('replicate', self::SUBJECT))
            && $this->canMutateNavigationSite($user, $navigation);
    }

    public function forceDelete(User $user, Navigation $navigation): bool
    {
        return $this->hasPermission($user, self::permission('force_delete', self::SUBJECT))
            && $this->canMutateNavigationSite($user, $navigation);
    }

    public function reorder(User $user): bool
    {
        return $this->hasPermission($user, self::permission('reorder', self::SUBJECT));
    }

    private function hasPermission(User $user, string $permission): bool
    {
        return SiteScope::isGlobalActor($user) || $user->checkPermissionTo($permission);
    }

    private function canUseNavigationSite(User $user, Navigation $navigation): bool
    {
        if ($navigation->site_id === null || SiteScope::isGlobalActor($user)) {
            return true;
        }

        try {
            return $user->getAssignedSiteIds()->contains((int) $navigation->site_id);
        } catch (Throwable) {
            return false;
        }
    }

    private function canMutateNavigationSite(User $user, Navigation $navigation): bool
    {
        if ($navigation->site_id === null) {
            return SiteScope::isGlobalActor($user);
        }

        return $this->canUseNavigationSite($user, $navigation);
    }
}
