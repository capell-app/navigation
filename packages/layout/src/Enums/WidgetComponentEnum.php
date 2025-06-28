<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

enum WidgetComponentEnum: string
{
    case Default = 'capell-layout::widget.default';

    case LivewirePages = 'capell-layout::livewire.widget.pages';

    case Navigation = 'capell-layout::widget.navigation';

    case Breadcrumbs = 'capell-layout::widget.breadcrumbs';
    case PageChildren = 'capell-layout::widget.pages.children';
    case PageContent = 'capell-layout::widget.page.content';
    case PageLatest = 'capell-layout::widget.pages.latest';
    case PageRelated = 'capell-layout::widget.pages.related';
    case PageSiblings = 'capell-layout::widget.pages.siblings';
    case PageSitemap = 'capell-layout::widget.page.sitemap';

    case Resources = 'capell-layout::widget.assets';
    case ResourcesAccordion = 'capell-layout::widget.assets.accordion';
    case ResourcesMedia = 'capell-layout::widget.assets.media';
    case ResourcesMediaCarousel = 'capell-layout::widget.assets.media.carousel';

    case Tags = 'capell-layout::widget.tag.tags';
}
