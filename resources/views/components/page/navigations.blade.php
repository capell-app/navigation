@php
    use Capell\Admin\Contracts\Support\FlagIconRenderer;
    use Capell\Core\Contracts\Pageable;
    use Capell\Navigation\Actions\BuildPageNavigationReferencesAction;
    use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;

    /* @var Pageable $record */
    $record = $getRecord();
    $flagIconRenderer = app(FlagIconRenderer::class);
    $navigations = BuildPageNavigationReferencesAction::run($record);
@endphp

<div>
    @if ($navigations->isNotEmpty())
        <h3 class="mb-2 text-lg font-semibold leading-tight">
            {{ __('capell-admin::generic.page_navigations') }}
        </h3>
        <div
            class="grid grid-cols-1 gap-4 divide-y divide-white/50 md:grid-cols-2"
        >
            @foreach ($navigations as $navigation)
                @php
                    $previous = ! $loop->first ? $navigations->get($loop->index - 1) : null;
                    $next = ! $loop->last ? $navigations->get($loop->index + 1) : null;
                @endphp

                @if ($loop->first || ($previous && $navigation->name !== $previous->name))
                    {{-- format-ignore-start --}}
                    <ul class="max-w-md space-y-1 text-gray-500 dark:text-gray-300">
                        {{-- format-ignore-end --}}
                @endif

                <li>
                    <x-filament::link
                        :href="
                            NavigationResource::getUrl(
                                'edit',
                                ['record' => $navigation],
                            )
                        "
                        tag="a"
                        size="xs"
                    >
                        {{ $navigation->name }}
                        @if ($navigation->language && $navigation->language->flag)
                            {!! $flagIconRenderer->render('flag-4x3-' . $navigation->language->flag, attributes: ['class' => 'ml-2 h-4 w-5']) !!}
                        @endif
                    </x-filament::link>
                </li>
                @if ($loop->last || ($next && $navigation->name !== $next->name))
                    {{-- format-ignore-start --}}</ul>{{-- format-ignore-end --}}
                @endif
            @endforeach
        </div>
    @endif
</div>
