<main class="capell-site-search-page">
    <h1>{{ __('capell-site-search::generic.page_title') }}</h1>

    <x-capell-site-search::form :query="$query" />

    <x-capell-site-search::results :query="$query" :results="$results" />
</main>
