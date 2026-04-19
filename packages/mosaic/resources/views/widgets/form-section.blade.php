{{-- Form Section Widget - Architectural Precision --}}
<section class="bg-mosaic-background py-mosaic-3xl px-mosaic-lg w-full">
    <div class="mx-auto max-w-2xl">
        {{-- Section label --}}
        <div
            class="text-mosaic-on-surface-variant font-mosaic-mono text-mosaic-label-sm mb-mosaic-lg uppercase tracking-widest"
        >
            [FORM: 006-A]
        </div>

        {{-- Form Header --}}
        <div class="mb-mosaic-2xl">
            <h2
                class="font-mosaic-headline text-mosaic-headline-md text-mosaic-on-surface mb-mosaic-md font-bold"
            >
                {{ $this->getFormTitle() }}
            </h2>
            @if ($description = $this->getFormDescription())
                <p class="text-mosaic-on-surface-variant text-mosaic-body-md">
                    {{ $description }}
                </p>
            @endif
        </div>

        {{-- Form --}}
        <form
            action="{{ $this->getSubmitAction() }}"
            method="POST"
            class="space-y-mosaic-lg"
        >
            @csrf

            {{-- Form Fields --}}
            @forelse ($this->getFormFields() as $index => $field)
                <div class="field-group">
                    {{-- Field Label with technical ID --}}
                    <label class="mb-mosaic-md block">
                        <span
                            class="text-mosaic-on-surface-variant font-mosaic-mono text-mosaic-label-sm uppercase tracking-widest"
                        >
                            [{{ $field['name'] ?? 'field-' . ($index + 1) }}]
                        </span>
                        <span
                            class="text-mosaic-on-surface mt-mosaic-sm block font-semibold"
                        >
                            {{ $field['label'] ?? ucfirst($field['name'] ?? 'Field') }}
                        </span>
                    </label>

                    {{-- Field Input --}}

                    @switch($field['type'] ?? 'text')
                        @case('textarea')
                            <textarea
                                name="{{ $field['name'] ?? 'field-' . ($index + 1) }}"
                                placeholder="{{ $field['placeholder'] ?? '' }}"
                                class="mosaic-input w-full"
                                @if(isset($field['required'])) required @endif
                                rows="{{ $field['rows'] ?? 4 }}"
                                style="border-radius: 0"
                            ></textarea>

                            @break
                        @case('select')
                            <select
                                name="{{ $field['name'] ?? 'field-' . ($index + 1) }}"
                                class="mosaic-input w-full"
                                @if(isset($field['required'])) required @endif
                                style="border-radius: 0"
                            >
                                <option value="">
                                    {{ $field['placeholder'] ?? 'Select an option' }}
                                </option>
                                @foreach ($field['options'] ?? [] as $value => $label)
                                    <option value="{{ $value }}">
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>

                            @break
                        @case('checkbox')
                            <div class="flex items-center">
                                <input
                                    type="checkbox"
                                    name="{{ $field['name'] ?? 'field-' . ($index + 1) }}"
                                    class="mosaic-input"
                                    @if(isset($field['required'])) required @endif
                                    style="
                                        width: 20px;
                                        height: 20px;
                                        border-radius: 0;
                                    "
                                />
                                <label
                                    class="ml-mosaic-md text-mosaic-on-surface-variant text-mosaic-body-md"
                                >
                                    {{ $field['checkbox_label'] ?? 'I agree' }}
                                </label>
                            </div>

                            @break
                        @default
                            <input
                                type="{{ $field['type'] ?? 'text' }}"
                                name="{{ $field['name'] ?? 'field-' . ($index + 1) }}"
                                placeholder="{{ $field['placeholder'] ?? '' }}"
                                class="mosaic-input w-full"
                                @if(isset($field['required'])) required @endif
                                style="border-radius: 0"
                            />
                    @endswitch

                    {{-- Help Text (if present) --}}
                    @if (isset($field['help_text']))
                        <p
                            class="text-mosaic-on-surface-variant text-mosaic-body-sm mt-mosaic-sm"
                        >
                            {{ $field['help_text'] }}
                        </p>
                    @endif
                </div>
            @empty
                <div
                    class="py-mosaic-lg text-mosaic-on-surface-variant text-center"
                >
                    No form fields configured
                </div>
            @endforelse

            {{-- Submit Button --}}
            @if ($this->getFieldCount() > 0)
                <div
                    class="pt-mosaic-lg border-mosaic-outline-variant border-t"
                >
                    <button
                        type="submit"
                        class="mosaic-btn mosaic-btn-primary w-full font-bold"
                        style="border-radius: 0"
                    >
                        {{ $this->getSubmitButtonText() }}
                    </button>
                </div>
            @endif
        </form>
    </div>
</section>
