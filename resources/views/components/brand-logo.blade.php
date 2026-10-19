{{--
    SPCC logo. Drop your real seal at public/images/spcc-logo.png and it is used automatically;
    until then a placeholder badge is drawn.
--}}
@props(['size' => 'size-16'])

@if (file_exists(public_path('images/spcc-logo.png')))
    <img src="{{ asset('images/spcc-logo.png') }}" alt="SPCC logo" {{ $attributes->class([$size, 'shrink-0 object-contain']) }}>
@else
    <svg viewBox="0 0 64 64" role="img" aria-label="SPCC logo" {{ $attributes->class([$size, 'shrink-0']) }}>
        <circle cx="32" cy="32" r="30" fill="#1e3a8a" stroke="#f59e0b" stroke-width="3" />
        <circle cx="32" cy="32" r="24" fill="none" stroke="#f59e0b" stroke-opacity=".45" stroke-width="1" />
        <path d="M32 17 11 27.5 32 38l21-10.5L32 17Z" fill="#ffffff" />
        <path d="M20 33.5v8c0 3.2 5.4 6 12 6s12-2.8 12-6v-8L32 39.5l-12-6Z" fill="#fbbf24" />
        <path d="M53 28v10" stroke="#ffffff" stroke-width="2" stroke-linecap="round" />
    </svg>
@endif
