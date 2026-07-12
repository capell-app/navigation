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

class Menu extends Component
{
    public function __construct(
        public NavigationHandle|string $key,
        public ?Site $site = null,
        public ?Language $language = null,
        public ?Pageable $page = null,
        public ?SiteDomain $domain = null,
        public bool $siteOnlyFallback = true,
    ) {}

    public function render(): ViewContract
    {
        return view('capell-navigation::components.menu', [
            'navigationKey' => $this->key,
            'navigationLabel' => $this->navigationLabel(),
            'site' => $this->site,
            'language' => $this->language,
            'page' => $this->page,
            'domain' => $this->domain,
            'siteOnlyFallback' => $this->siteOnlyFallback,
        ]);
    }

    private function navigationLabel(): string
    {
        $handle = $this->key instanceof NavigationHandle
            ? $this->key
            : NavigationHandle::tryFrom($this->key);

        return match ($handle) {
            NavigationHandle::Main => __('capell-navigation::generic.main_navigation'),
            NavigationHandle::Footer => __('capell-navigation::generic.footer_navigation'),
            NavigationHandle::SubFooter => __('capell-navigation::generic.sub_footer_navigation'),
            default => __('capell-navigation::generic.navigation'),
        };
    }
}
