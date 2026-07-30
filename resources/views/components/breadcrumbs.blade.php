@php
    use Capell\Navigation\Actions\BuildNavigationBreadcrumbsAction;
    use Capell\Navigation\Data\NavigationRenderData;
    use Capell\Navigation\Support\SafeUrl;

    $breadcrumbs = ($menu ?? null) instanceof NavigationRenderData
        ? BuildNavigationBreadcrumbsAction::run($menu)
        : collect();
@endphp

@if ($breadcrumbs->isNotEmpty())
    <nav
        {{ $attributes->merge(['aria-label' => __('capell-navigation::generic.breadcrumb_navigation')]) }}
    >
        <ol class="capell-navigation-breadcrumbs">
            @foreach ($breadcrumbs as $breadcrumb)
                @php($safeBreadcrumbUrl = SafeUrl::sanitise($breadcrumb->url))
                <li @class(['is-active' => $loop->last])>
                    @if (! $loop->last && $safeBreadcrumbUrl !== null)
                        <a
                            href="{{ $safeBreadcrumbUrl }}"
                            @if ($breadcrumb->target !== null) target="{{ $breadcrumb->target }}" @endif
                            @if ($breadcrumb->rel !== null) rel="{{ $breadcrumb->rel }}" @endif
                        >
                            {{ $breadcrumb->label }}
                        </a>
                    @else
                        <span>{{ $breadcrumb->label }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
