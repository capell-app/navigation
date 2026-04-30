<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Http\Controllers;

use Capell\SiteSearch\Actions\RunSiteSearchAction;
use Capell\SiteSearch\Data\SearchRequestData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class SearchController
{
    public function __invoke(Request $request): View
    {
        $query = (string) $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) config('capell-site-search.results_per_page', 10);

        $site = $request->attributes->get('site');
        $language = $request->attributes->get('language');

        $data = new SearchRequestData(
            query: $query,
            page: $page,
            perPage: $perPage,
            siteId: is_object($site) ? (int) data_get($site, 'id') : null,
            languageId: is_object($language) ? (int) data_get($language, 'id') : null,
        );

        $results = RunSiteSearchAction::run($data);

        return view('capell-site-search::pages.search', [
            'query' => $query,
            'results' => $results,
        ]);
    }
}
