<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Navigation\Data\NavigationItemRenderData;
use Capell\Navigation\Support\SafeUrl;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Describes the visible breadcrumb trail as a schema.org BreadcrumbList. Only the public label and the absolute
 * address of each crumb are included; crumbs without a web address (headings, mailto and tel links) are left out.
 *
 * @method static array<string, mixed>|null run(Collection<int, NavigationItemRenderData> $breadcrumbs)
 */
final class BuildNavigationBreadcrumbListSchemaAction
{
    use AsFake;
    use AsObject;

    /**
     * @param  Collection<int, NavigationItemRenderData>  $breadcrumbs
     * @return array<string, mixed>|null
     */
    public function handle(Collection $breadcrumbs): ?array
    {
        $elements = [];
        $lastIndex = $breadcrumbs->count() - 1;

        foreach ($breadcrumbs->values() as $index => $breadcrumb) {
            $name = trim(strip_tags((string) $breadcrumb->label));
            $url = $this->absoluteUrl($breadcrumb->url);

            if ($name === '' || ($url === null && $index !== $lastIndex)) {
                continue;
            }

            $element = [
                '@type' => 'ListItem',
                'position' => count($elements) + 1,
                'name' => $name,
            ];

            if ($url !== null) {
                $element['item'] = $url;
            }

            $elements[] = $element;
        }

        if (count($elements) < 2) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    private function absoluteUrl(?string $url): ?string
    {
        $safeUrl = SafeUrl::sanitise($url);

        if ($safeUrl === null) {
            return null;
        }

        $scheme = parse_url($safeUrl, PHP_URL_SCHEME);

        if (is_string($scheme)) {
            return in_array(strtolower($scheme), ['http', 'https'], true) ? $safeUrl : null;
        }

        return str_starts_with($safeUrl, '/') ? url($safeUrl) : null;
    }
}
