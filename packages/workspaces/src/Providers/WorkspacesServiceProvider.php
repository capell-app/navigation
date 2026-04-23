<?php

declare(strict_types=1);

namespace Capell\Workspaces\Providers;

use Capell\Core\Models\AssetRelation;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Navigation;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Theme;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;
use Capell\Workspaces\Http\Middleware\ResolveWorkspaceContext;
use Capell\Workspaces\Listeners\StampWorkspaceOnActivity;
use Capell\Workspaces\Models\PreviewLink;
use Capell\Workspaces\Models\Version;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Models\WorkspaceApproval;
use Capell\Workspaces\Models\WorkspaceFieldComment;
use Capell\Workspaces\Models\WorkspaceReviewAssignment;
use Capell\Workspaces\WorkspaceRegistry;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Contracts\Routing\Registrar as Router;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class WorkspacesServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->registerMorphMap()
            ->registerWorkspaceDraftables()
            ->registerMiddleware()
            ->registerEventListeners();
    }

    private function registerMorphMap(): self
    {
        Relation::morphMap([
            'workspace' => Workspace::class,
            'workspace_approval' => WorkspaceApproval::class,
            'workspace_field_comment' => WorkspaceFieldComment::class,
            'workspace_review_assignment' => WorkspaceReviewAssignment::class,
            'version' => Version::class,
            'preview_link' => PreviewLink::class,
        ]);

        return $this;
    }

    private function registerWorkspaceDraftables(): self
    {
        $simpleModels = [
            Navigation::class,
            Site::class,
            SiteDomain::class,
            Type::class,
            Theme::class,
            Layout::class,
            Language::class,
            Media::class,
            Translation::class,
            PageUrl::class,
            AssetRelation::class,
        ];

        foreach ($simpleModels as $modelClass) {
            WorkspaceRegistry::register($modelClass);
        }

        // Page requires a finalizeOnPublish hook to retarget PageUrl + Translation rows.
        WorkspaceRegistry::register(Page::class, finalizeOnPublish: static function (Page $draftRow): Page {
            if ($draftRow->uuid === null || $draftRow->uuid === '') {
                return $draftRow;
            }

            $workspaceId = $draftRow->workspace_id;
            $draftPageId = (int) $draftRow->getKey();
            $morphClass = $draftRow->getMorphClass();

            $oldLiveId = Page::query()
                ->withoutGlobalScopes()
                ->where('uuid', $draftRow->uuid)
                ->where('workspace_id', 0)
                ->value('id');

            if ($oldLiveId === null) {
                return $draftRow;
            }

            PageUrl::query()
                ->withoutGlobalScopes()
                ->where('pageable_type', $morphClass)
                ->where('pageable_id', $oldLiveId)
                ->where('workspace_id', 0)
                ->update(['pageable_id' => $draftPageId]);

            $coveredLanguageIds = Translation::query()
                ->withoutGlobalScopes()
                ->where('translatable_type', $morphClass)
                ->where('translatable_id', $draftPageId)
                ->where('workspace_id', $workspaceId)
                ->pluck('language_id')
                ->all();

            $translationQuery = Translation::query()
                ->withoutGlobalScopes()
                ->where('translatable_type', $morphClass)
                ->where('translatable_id', $oldLiveId)
                ->where('workspace_id', 0);

            if ($coveredLanguageIds !== []) {
                $translationQuery->whereNotIn('language_id', $coveredLanguageIds);
            }

            $translationQuery->update(['translatable_id' => $draftPageId]);

            return $draftRow;
        });

        return $this;
    }

    private function registerMiddleware(): self
    {
        if ($this->app->bound(HttpKernel::class) && $this->app->bound(Router::class)) {
            $this->app->make(Router::class)
                ->aliasMiddleware('workspace.context', ResolveWorkspaceContext::class);
        }

        return $this;
    }

    private function registerEventListeners(): self
    {
        Event::listen(
            'Spatie\Activitylog\Events\ActivitySaved',
            [StampWorkspaceOnActivity::class, 'handle'],
        );

        return $this;
    }
}
