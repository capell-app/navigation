@props([
    'query' => '',
])

<form
    method="GET"
    action="{{ route('capell-frontend.search') }}"
    role="search"
    class="capell-site-search-form"
>
    <label class="sr-only" for="capell-site-search-query">
        {{ __('capell-site-search::generic.search_label') }}
    </label>
    <input
        id="capell-site-search-query"
        type="search"
        name="q"
        value="{{ $query }}"
        placeholder="{{ __('capell-site-search::generic.search_placeholder') }}"
    />
    <button type="submit">
        {{ __('capell-site-search::button.search') }}
    </button>
</form>
