<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $roleLabel }} | SPCC AI Learning Assistant</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-100 font-sans text-slate-800 antialiased">
    {{-- Placeholder page so login can be tested. Replaced by the real role layouts next. --}}
    <header class="flex items-center justify-between bg-blue-950 px-6 py-3 text-white">
        <div class="flex items-center gap-3">
            <x-brand-logo size="size-9" />
            <span class="font-semibold">SPCC AI <span class="font-normal text-blue-200">Learning Assistant</span></span>
        </div>

        <div class="flex items-center gap-4 text-sm">
            <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
            <span class="rounded-full bg-white/10 px-2.5 py-0.5 text-xs font-medium">{{ $roleLabel }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-md bg-white/10 px-3 py-1.5 font-medium hover:bg-white/20">Logout</button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-6 py-16 text-center">
        <h1 class="text-2xl font-bold text-slate-900">Welcome, {{ auth()->user()->name }}!</h1>
        <p class="mt-2 text-slate-600">You are signed in as <strong>{{ $roleLabel }}</strong>. The {{ strtolower($roleLabel) }} dashboard is built in the next step.</p>
    </main>
</body>
</html>
