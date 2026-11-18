@php
    use Capell\Navigation\Actions\BuildNavigationBreadcrumbListSchemaAction;
    use Capell\Navigation\Actions\BuildNavigationBreadcrumbsAction;
    use Capell\Navigation\Data\NavigationRenderData;
    use Capell\Navigation\Support\SafeUrl;

    $breadcrumbs = ($menu ?? null) instanceof NavigationRenderData
        ? BuildNavigationBreadcrumbsAction::run($menu)
        : collect();
    $breadcrumbListSchema = BuildNavigationBreadcrumbListSchemaAction::run($breadcrumbs);
@endphp

@if ($breadcrumbs->isNotEmpty())
    <nav
        {{ $attributes->merge(['aria-label' => __('capell-navigation::public.landmark.breadcrumb')]) }}
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

    @if ($breadcrumbListSchema !== null)
        <script
            type="application/ld+json"
        >{!! json_encode($breadcrumbListSchema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) !!}</script>
    @endif
@endif
