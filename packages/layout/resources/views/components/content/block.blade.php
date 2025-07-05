@props([
    'title',
    'summary',
    'color' => null,
    'image' => null,
    'icon' => null,
    'loop' => null,
    'tags' => null,
    'linkText' => null,
    'url' => null,
    'withSummary' => true,
])
<div
    @class([
        'flex flex-col items-center p-10 text-center md:p-16 lg:p-20 xl:p-24',
        'bg-gray-100 text-gray-900' => is_null($color),
        'bg-white text-gray-900' => $color === 'white',
        'bg-gray-200 text-gray-900' => $color === 'gray',
        'bg-blue-100 text-blue-900' => $color === 'info',
        'bg-green-100 text-green-900' => $color === 'success',
        'bg-yellow-100 text-yellow-900' => $color === 'warning',
        'bg-red-100 text-red-900' => $color === 'danger',
        'bg-primary text-white' => $color === 'primary',
        'bg-secondary text-white' => $color === 'secondary',
    ])
>
    @if ($icon)
        <div class="mb-6">
            <x-capell::icon
                :$icon
                class="mx-auto h-12 w-12 text-gray-500 dark:text-gray-400"
            />
        </div>
    @endif

    @if ($title)
        <h2 class="mb-4 text-2xl font-bold">{{ $title }}</h2>
    @endif

    @if ($withSummary && $summary)
        <div class="prose prose-lg">
            {!! $summary !!}
        </div>
    @endif
</div>
