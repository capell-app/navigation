<?php

declare(strict_types=1);

namespace Capell\Layout\Services\Creator;

use Capell\Admin\Enums\LayoutEnum;
use Capell\Core\Models\Layout;
use InvalidArgumentException;

class LayoutUpdater
{
    public function setup(?string $key = null): void
    {
        if ($key === null) {
            $this->defaultLayout(Layout::query()->firstWhere('key', LayoutEnum::Default));
            $this->homeLayout(Layout::query()->firstWhere('key', LayoutEnum::Home));
            $this->resultsLayout(Layout::query()->firstWhere('key', LayoutEnum::Results));
            $this->tagsLayout(Layout::query()->firstWhere('key', LayoutEnum::Tags));

            $this->addHeroContainerToOtherLayouts();

            return;
        }

        match ($key) {
            LayoutEnum::Home->value => $this->homeLayout(Layout::query()->firstWhere('key', LayoutEnum::Home)),
            LayoutEnum::Results->value => $this->resultsLayout(Layout::query()->firstWhere('key', LayoutEnum::Results)),
            LayoutEnum::Tags->value => $this->tagsLayout(Layout::query()->firstWhere('key', LayoutEnum::Tags)),
            LayoutEnum::Default->value => $this->defaultLayout(Layout::query()->firstWhere('key', LayoutEnum::Default)),
            default => throw new InvalidArgumentException('Invalid layout key: ' . $key)
        };
    }

    public function defaultLayout(Layout $layout): void
    {
        $layout->update([
            'containers' => [
                'main' => $this->mainContainer([
                    ['widget_key' => 'breadcrumbs'],
                    ['widget_key' => 'page-content'],
                    ['widget_key' => 'children'],
                ]),
                'sidebar' => $this->sidebarContainer([
                    ['widget_key' => 'latest-pages'],
                ]),
            ],
        ]);
    }

    public function homeLayout(Layout $layout): void
    {
        $layout->update([
            'containers' => [
                'hero' => $this->heroContainer(),
                'main' => [
                    'widgets' => [
                        ['widget_key' => 'page-content'],
                    ],
                ],
            ],
        ]);
    }

    public function resultsLayout(Layout $layout): void
    {
        $layout->update([
            'containers' => [
                'hero' => $this->heroContainer(),
                'main' => $this->mainContainer([
                    ['widget_key' => 'breadcrumbs'],
                    ['widget_key' => 'page-content'],
                    ['widget_key' => 'page-slot'],
                ]),
                'sidebar' => $this->sidebarContainer([
                    ['widget_key' => 'latest-pages'],
                ]),
            ],
        ]);
    }

    public function tagsLayout(Layout $layout): void
    {
        $layout->update([
            'containers' => [
                'hero' => $this->heroContainer(),
                'main' => $this->mainContainer([
                    ['widget_key' => 'tags', 'meta' => ['show_page_title' => true]],
                ]),
                'sidebar' => $this->sidebarContainer([
                    ['widget_key' => 'latest-pages'],
                ]),
            ],
        ]);
    }

    private function addHeroContainerToOtherLayouts(): void
    {
        Layout::query()->whereNotIn('key', [
            LayoutEnum::Default->value,
            LayoutEnum::Home->value,
            LayoutEnum::Results->value,
            LayoutEnum::Tags->value,
        ])
            ->each(function (Layout $layout): void {
                $containers = $layout->containers ?? [];

                if (! array_key_exists('hero', $containers)) {
                    $containers = array_merge(['hero' => $this->heroContainer()], $containers);

                    $layout->update(['containers' => $containers]);
                }
            });
    }

    private function heroContainer(): array
    {
        return [
            'meta' => [
                'colspan' => 12,
                'container' => 'full',
            ],
            'widgets' => [
                ['widget_key' => 'hero'],
            ],
        ];
    }

    private function sidebarContainer(array $widgets): array
    {
        return [
            'meta' => [
                'colspan' => 3,
                'override_columns' => 1,
                'container' => 'full',
                'tag' => 'aside',
                'padding' => ['md'],
                'html_class' => 'sidebar-sticky space-y-10',
            ],
            'widgets' => $widgets,
        ];
    }

    private function mainContainer(array $widgets): array
    {
        return [
            'meta' => [
                'colspan' => 9,
            ],
            'widgets' => $widgets,
        ];
    }
}
