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
        if ($user->checkPermissionTo(self::permission('view_any', self::SUBJECT))) {
            return true;
        }

        return $user->checkPermissionTo(self::permission('view', self::SUBJECT));
    }

    public function view(User $user, Navigation $navigation): bool
    {
        return (
            $user->checkPermissionTo(self::permission('view_any', self::SUBJECT))
            || $user->checkPermissionTo(self::permission('view', self::SUBJECT))
        )
            && $this->canUseNavigationSite($user, $navigation);
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo(self::permission('create', self::SUBJECT));
    }

    public function update(User $user, Navigation $navigation): bool
    {
        return $user->checkPermissionTo(self::permission('update', self::SUBJECT))
            && $this->canUseNavigationSite($user, $navigation);
    }

    public function delete(User $user, Navigation $navigation): bool
    {
        return $user->checkPermissionTo(self::permission('delete', self::SUBJECT))
            && $this->canUseNavigationSite($user, $navigation);
    }

    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo(self::permission('delete_any', self::SUBJECT));
    }

    public function restore(User $user, Navigation $navigation): bool
    {
        return $user->checkPermissionTo(self::permission('restore', self::SUBJECT))
            && $this->canUseNavigationSite($user, $navigation);
    }

    public function forceDelete(User $user, Navigation $navigation): bool
    {
        return $user->checkPermissionTo(self::permission('force_delete', self::SUBJECT))
            && $this->canUseNavigationSite($user, $navigation);
    }

    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo(self::permission('reorder', self::SUBJECT));
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
}
