<?php

declare(strict_types=1);

namespace Capell\Navigation\Data;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

class NavigationRenderContextData extends Data
{
    /**
     * @param  Model&Pageable<Model>  $page
     */
    public function __construct(
        public Navigation $navigation,
        public Pageable $page,
        public Site $site,
        public Language $language,
        public SiteDomain $siteDomain,
    ) {}
}
