@props(['title' => 'Sign in'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | SPCC AI Learning Assistant</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-100 font-sans text-slate-800 antialiased">
    <div class="flex min-h-screen">

        {{-- Brand panel (desktop only) --}}
        <aside class="relative hidden w-[40%] max-w-xl flex-col items-center justify-center overflow-hidden bg-gradient-to-b from-blue-950 via-blue-900 to-blue-800 p-12 text-center text-white lg:flex">
            <div class="pointer-events-none absolute -right-24 -top-24 size-80 rounded-full bg-white/5"></div>
            <div class="pointer-events-none absolute -bottom-32 -left-20 size-96 rounded-full bg-white/5"></div>

            <x-brand-logo size="size-28" class="relative" />
            <h2 class="relative mt-8 text-4xl font-bold tracking-tight">SPCC AI</h2>
            <p class="relative mt-1 text-xl font-medium text-blue-100">Learning Assistant</p>
            <p class="relative mt-10 text-lg leading-relaxed text-blue-200">Learn Smarter.<br>Achieve More.</p>
        </aside>

        {{-- Form side --}}
        <main class="flex flex-1 flex-col items-center justify-center px-4 py-10">
            <div class="w-full max-w-md">
                <div class="rounded-2xl bg-white p-6 shadow-xl shadow-blue-900/10 ring-1 ring-slate-200 sm:p-8">
                    <div class="flex flex-col items-center text-center">
                        <x-brand-logo size="size-16" />
                        <p class="mt-3 text-xl font-bold text-blue-950">SPCC AI</p>
                        <p class="text-sm text-slate-500">Learning Assistant</p>
                    </div>

                    <div class="mt-6 border-t border-slate-100 pt-6">
                        {{ $slot }}
                    </div>
                </div>

                <p class="mt-6 text-center text-xs text-slate-400">&copy; {{ date('Y') }} System Plus Computer College</p>
            </div>
        </main>
    </div>

    <script>
        // show / hide password
        document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.dataset.togglePassword);
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.querySelector('[data-eye]').classList.toggle('hidden', show);
                btn.querySelector('[data-eye-off]').classList.toggle('hidden', !show);
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
