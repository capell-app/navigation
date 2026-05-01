@props(['analytics' => null, 'utm' => null])

@if ($analytics && $analytics->isEnabled())
    {!! $analytics->renderInitScript() !!}
    @if ($utm)
        <script>
            {!! $utm->toJavaScript() !!}
        </script>
    @endif
@endif
