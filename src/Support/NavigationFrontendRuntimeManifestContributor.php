<?php

declare(strict_types=1);

namespace Capell\Navigation\Support;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Theme;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Contracts\FrontendRuntimeManifestContributor;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Data\NavigationRenderContextData;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Loader\NavigationLoader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class NavigationFrontendRuntimeManifestContributor implements FrontendRuntimeManifestContributor
{
    public static function renderModelKey(string $navigationKey): string
    {
        return 'navigation.render_model.' . $navigationKey;
    }

    public function contribute(FrontendContextReader $context, FrontendRuntimeManifestData $manifest): void
    {
        $page = $context->page();
        $site = $context->site();
        $language = $context->language();

        if (! $page instanceof Pageable || ! $page instanceof Model || ! $site instanceof Site || ! $language instanceof Language) {
            return;
        }

        $page->loadMissing('pageUrl.siteDomain');
        $site->loadMissing('siteDomain');
        $theme = $context->theme();

        if ($theme instanceof Theme) {
            $theme->loadMissing('blueprint');
        }

        $this->hydrateSiteNavigations($site);

        $siteDomain = $this->siteDomain($site, $page);

        if (! $siteDomain instanceof SiteDomain) {
            return;
        }

        foreach (NavigationHandle::cases() as $handle) {
            $navigation = NavigationLoader::getNavigation($handle, $site, $language);

            if (! $navigation instanceof Navigation) {
                continue;
            }

            $navigation->loadMissing('blueprint');

            $context->setFrontendData(
                $this->renderModelKey($handle->value),
                BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
                    navigation: $navigation,
                    page: $page,
                    site: $site,
                    language: $language,
                    siteDomain: $siteDomain,
                )),
            );
        }
    }

    private function hydrateSiteNavigations(Site $site): void
    {
        if ($site->relationLoaded('navigations')) {
            $site->navigations->loadMissing('blueprint');

            return;
        }

        $site->setRelation(
            'navigations',
            Navigation::query()
                ->where(fn (Builder $query): Builder => $query
                    ->where('site_id', $site->getKey())
                    ->orWhereNull('site_id'))
                ->with('blueprint')
                ->publishedDate()
                ->get(),
        );
    }

    private function siteDomain(Site $site, Model $page): ?SiteDomain
    {
        if ($site->relationLoaded('siteDomain') && $site->siteDomain instanceof SiteDomain) {
            return $site->siteDomain;
        }

        $pageUrl = $page->relationLoaded('pageUrl') ? $page->getRelation('pageUrl') : null;

        if (! $pageUrl instanceof Model || ! $pageUrl->relationLoaded('siteDomain')) {
            return null;
        }

        $siteDomain = $pageUrl->getRelation('siteDomain');

        return $siteDomain instanceof SiteDomain ? $siteDomain : null;
    }
}
