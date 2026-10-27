<div role="status">
    @if ($rows === [])
        <p>{{ __('capell-navigation::generic.saved_items_empty') }}</p>
    @else
        <ol class="space-y-3">
            @foreach ($rows as $row)
                <li
                    data-item-position="{{ $row->position }}"
                    data-availability="{{ $row->status }}"
                >
                    <span
                        class="font-medium"
                        >{{ __('capell-navigation::generic.saved_item_position', ['position' => $row->position]) }}</span
                    >
                    <p>{{ __('capell-navigation::generic.saved_item_status.' . $row->status) }}</p>
                </li>
            @endforeach
        </ol>
    @endif
</div>
