<?php

declare(strict_types=1);

namespace Capell\Workspaces\Providers;

use Capell\Workspaces\Events\WorkspaceStateChanged;
use Capell\Workspaces\Listeners\SendWorkspaceStateNotification;
use Capell\Workspaces\Livewire\WorkspaceApprovalHistory;
use Capell\Workspaces\Livewire\WorkspaceContextBanner;
use Capell\Workspaces\Livewire\WorkspaceSwitcher;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Policies\WorkspacePolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->registerLivewireComponents()
            ->registerEventListeners()
            ->registerPolicies();
    }

    private function registerLivewireComponents(): self
    {
        Livewire::component('capell-workspaces::workspace-switcher', WorkspaceSwitcher::class);
        Livewire::component('capell-workspaces::workspace-context-banner', WorkspaceContextBanner::class);
        Livewire::component('capell-workspaces::workspace-approval-history', WorkspaceApprovalHistory::class);

        Livewire::addNamespace(
            namespace: 'capell-workspaces',
            classNamespace: 'Capell\\Workspaces\\Livewire',
            classPath: __DIR__ . '/../Livewire',
        );

        return $this;
    }

    private function registerEventListeners(): self
    {
        Event::listen(WorkspaceStateChanged::class, SendWorkspaceStateNotification::class);

        return $this;
    }

    private function registerPolicies(): self
    {
        Gate::policy(Workspace::class, WorkspacePolicy::class);

        return $this;
    }
}
