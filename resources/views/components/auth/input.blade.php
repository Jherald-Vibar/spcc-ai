{{--
    Labelled text field with a leading icon; password fields get a show/hide button
    (wired up by the script in layouts/auth). Any extra attributes (placeholder,
    autocomplete, required...) are passed to the <input>.
--}}
@props(['name', 'label', 'icon' => null, 'type' => 'text', 'value' => null])

@php
    $hasError = $errors->has($name);
    $inputClass = 'block w-full rounded-lg border bg-white py-2.5 text-sm text-slate-900 placeholder:text-slate-400 '
        . 'focus:outline-none focus:ring-2 '
        . ($hasError
            ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20'
            : 'border-slate-300 focus:border-blue-600 focus:ring-blue-600/20')
        . ($icon ? ' pl-10' : ' pl-3')
        . ($type === 'password' ? ' pr-10' : ' pr-3');
@endphp

<div>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-slate-700">{{ $label }}</label>

    <div class="relative">
        @if ($icon)
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                <x-icon :name="$icon" class="size-5" />
            </span>
        @endif

        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
            {{ $attributes->merge(['class' => $inputClass]) }}
        >

        @if ($type === 'password')
            <button type="button" data-toggle-password="{{ $name }}" aria-label="Show password"
                    class="absolute inset-y-0 right-0 flex items-center rounded-r-lg px-3 text-slate-400 hover:text-slate-600 focus:outline-none focus-visible:text-blue-700">
                <x-icon name="eye" class="size-5" data-eye="1" />
                <x-icon name="eye-slash" class="hidden size-5" data-eye-off="1" />
            </button>
        @endif
    </div>
</div>
