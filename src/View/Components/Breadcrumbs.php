<?php

declare(strict_types=1);

namespace Capell\Navigation\View\Components;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Navigation\Enums\NavigationHandle;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\View\Component;

final class Breadcrumbs extends Component
{
    public function __construct(
        public NavigationHandle|string $key = NavigationHandle::Main,
        public ?Site $site = null,
        public ?Language $language = null,
        public ?Pageable $page = null,
        public ?SiteDomain $domain = null,
        public bool $siteOnlyFallback = true,
    ) {}

    public function render(): ViewContract
    {
        return view('capell-navigation::components.breadcrumbs', [
            'navigationKey' => $this->key,
            'site' => $this->site,
            'language' => $this->language,
            'page' => $this->page,
            'domain' => $this->domain,
            'siteOnlyFallback' => $this->siteOnlyFallback,
        ]);
    }
}
