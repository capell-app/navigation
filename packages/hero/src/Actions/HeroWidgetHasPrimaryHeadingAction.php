<?php

declare(strict_types=1);

namespace Capell\Hero\Actions;

use Capell\Core\Models\Page;
use Capell\Frontend\Facades\Frontend;
use Capell\Layout\Models\Widget;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static bool run(Widget $widget, Page $page)
 */
class HeroWidgetHasPrimaryHeadingAction
{
    use AsObject;

    public function handle(Widget $widget, Page $page): bool
    {
        $hasPrimaryHeading = false;

        if ($widget->assets->isNotEmpty()) {
            $firstAssetTranslation = $widget->assets->first()?->asset->translation;

            if ($firstAssetTranslation) {
                if ($firstAssetTranslation->title) {
                    $hasPrimaryHeading = true;
                } elseif ($firstAssetTranslation->content) {
                    $content = $firstAssetTranslation->content;
                }
            }
        } else {
            $content = $page->translation->meta['hero'] ?? null;
        }

        if (! $hasPrimaryHeading && ! empty($content)) {
            $hasPrimaryHeading = preg_match('/<h1\b[^>]*>/i', (string) $content) === 1;
        }

        if ($hasPrimaryHeading) {
            Frontend::setFrontendData('has_primary_heading', true);
        }

        return $hasPrimaryHeading;
    }
}
