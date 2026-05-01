@props(['items' => [], 'label' => 'Menu'])
<div x-data="{ open: false }" class="md:hidden">
    <button
        @click="open = !open"
        :aria-expanded="open.toString()"
        aria-controls="mobile-nav"
        class="flex min-h-[44px] items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500"
        type="button"
    >
        <span x-text="open ? '✕' : '☰'" aria-hidden="true"></span>
        <span class="sr-only">{{ $label }}</span>
    </button>
    <nav
        id="mobile-nav"
        x-show="open"
        x-transition
        class="mt-2 space-y-1 rounded-lg border bg-white p-2 shadow-lg"
    >
        @foreach ($items as $item)
            <a
                href="{{ $item['url'] ?? '#' }}"
                class="flex min-h-[44px] items-center rounded px-3 py-2 text-sm hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
                {{ $item['label'] ?? '' }}
            </a>
        @endforeach
    </nav>
</div>
