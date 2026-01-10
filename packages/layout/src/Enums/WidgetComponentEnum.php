<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\View\Components\Widget\Page\ChildrenWidget;
use Capell\Layout\View\Components\Widget\Page\LatestWidget;
use Capell\Layout\View\Components\Widget\Page\SiblingsWidget;

enum WidgetComponentEnum: string
{
    case AssetAccordion = 'capell-layout::widget.asset.accordion';
    case AssetBanner = 'capell-layout::widget.asset.banners';
    case AssetBlock = 'capell-layout::widget.asset.blocks';
    case AssetCarousel = 'capell-layout::widget.asset.carousel';
    case AssetFeatures = 'capell-layout::widget.asset.features';
    case AssetMedia = 'capell-layout::widget.asset.media';
    case AssetTestimonials = 'capell-layout::widget.asset.testimonials';
    case Assets = 'capell-layout::widget.asset';
    case BannerImage = 'capell-layout::widget.banner-image';
    case Default = 'capell-layout::widget.default';
    case Navigation = 'capell-layout::widget.navigation';
    case NavigationTabs = 'capell-layout::widget.navigation.tabs';
    case PageBreadcrumbs = 'capell-layout::widget.page.breadcrumbs';
    case PageChildren = 'capell-layout::widget.page.children';
    case PageContent = 'capell-layout::widget.page.content';
    case PageLatest = 'capell-layout::widget.page.latest';
    case PageSiblings = 'capell-layout::widget.page.siblings';
    case PageSlot = 'capell-layout::widget.slot';
    case Pages = 'capell-layout::widget.asset.pages';

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
