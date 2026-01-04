<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\View\Components\Widget\Pages\ChildrenWidget;
use Capell\Layout\View\Components\Widget\Pages\LatestWidget;
use Capell\Layout\View\Components\Widget\Pages\SiblingsWidget;

enum WidgetComponentEnum: string
{
    case Default = 'capell-layout::widget.default';

    case BannerImage = 'capell-layout::widget.banner-image';

    case Navigation = 'capell-layout::widget.navigation';

    case NavigationTabs = 'capell-layout::widget.navigation.tabs';

    case PageChildren = 'capell-layout::widget.pages.children';

    case PageLatest = 'capell-layout::widget.pages.latest';

    case PageSiblings = 'capell-layout::widget.pages.siblings';

    case Breadcrumbs = 'capell-layout::widget.page.breadcrumbs';

    case PageContent = 'capell-layout::widget.page.content';

    case Pages = 'capell-layout::widget.page.pages';

    case Assets = 'capell-layout::widget.assets';

    case AssetAccordion = 'capell-layout::widget.assets.accordion';

    case AssetBanner = 'capell-layout::widget.assets.banners';

    case AssetBlock = 'capell-layout::widget.assets.blocks';

    case AssetCarousel = 'capell-layout::widget.assets.carousel';

    case AssetFeatures = 'capell-layout::widget.assets.features';

    case AssetMedia = 'capell-layout::widget.assets.media';

    case AssetTestimonials = 'capell-layout::widget.assets.testimonials';

    case PageSlot = 'capell-layout::widget.slot';

    public static function getComponents(): array
    {
        $components = [];
        foreach (self::cases() as $widgetComponent) {
            $components[$widgetComponent->value] = $widgetComponent->getComponent();
        }

        return $components;
    }

    public function getComponent(): ?string
    {
        return match ($this) {
            self::PageChildren => ChildrenWidget::class,
            self::PageSiblings => SiblingsWidget::class,
            self::PageLatest => LatestWidget::class,
            default => null
        };
    }
}
