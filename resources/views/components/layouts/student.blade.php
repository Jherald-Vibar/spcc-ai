@props(['title' => 'Dashboard', 'active' => 'dashboard'])
@php
    $user = auth()->user();
    $initials = collect(preg_split('/[\s_]+/', trim($user->name ?? 'Student')))
        ->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');

    $nav = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home',      'route' => 'student.dashboard'],
        ['key' => 'lessons',   'label' => 'My Lessons', 'icon' => 'book-open', 'route' => 'student.lessons'],
        ['key' => 'profile',   'label' => 'Profile',    'icon' => 'user',      'route' => 'student.profile'],
        ['key' => 'settings',  'label' => 'Settings',   'icon' => 'cog',       'route' => 'student.settings'],
    ];
@endphp
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

    {{-- Mobile backdrop --}}
    <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden"></div>

    {{-- Sidebar --}}
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-gradient-to-b from-blue-950 via-blue-900 to-blue-950 text-blue-100 transition-transform duration-200 lg:translate-x-0">
        <div class="flex items-center gap-3 px-5 py-5">
            <x-brand-logo size="size-11" />
            <div class="leading-tight">
                <p class="text-base font-bold text-white">SPCC AI</p>
                <p class="text-xs text-blue-200">Learning Assistant</p>
            </div>
            <button type="button" data-sidebar-close class="ml-auto rounded-md p-1 text-blue-200 hover:bg-white/10 lg:hidden" aria-label="Close menu">
                <x-icon name="x-mark" class="size-5" />
            </button>
        </div>

        <nav class="mt-2 flex-1 space-y-1 px-3" aria-label="Main">
            @foreach ($nav as $item)
                @php($isActive = $active === $item['key'])
                <a href="{{ route($item['route']) }}"
                   @if ($isActive) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                          {{ $isActive ? 'bg-blue-600 text-white shadow-sm' : 'text-blue-100 hover:bg-white/10 hover:text-white' }}">
                    <x-icon :name="$item['icon']" class="size-5" />
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('logout') }}" class="p-3">
            @csrf
            <button type="submit"
                    class="flex w-full items-center gap-3 rounded-lg bg-white/10 px-3 py-2.5 text-sm font-medium text-white transition hover:bg-white/20">
                <x-icon name="logout" class="size-5" />
                Logout
            </button>
        </form>
    </aside>

    <div class="lg:pl-64">
        {{-- Top bar --}}
        <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-200 bg-white/90 px-4 py-3 backdrop-blur sm:px-6">
            <button type="button" id="sidebar-open" class="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Open menu">
                <x-icon name="bars-3" class="size-6" />
            </button>

            <h1 class="truncate text-base font-semibold text-slate-900 sm:text-lg">{{ $title }}</h1>

            <details class="relative ml-auto">
                <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-100 [&::-webkit-details-marker]:hidden">
                    <span class="grid size-8 place-items-center rounded-full bg-blue-700 text-xs font-semibold text-white">{{ $initials }}</span>
                    <span class="hidden text-sm font-medium text-slate-700 sm:inline">Student</span>
                    <x-icon name="chevron-down" class="size-4 text-slate-500" />
                </summary>
                <div class="absolute right-0 mt-2 w-56 rounded-xl bg-white p-2 shadow-lg ring-1 ring-slate-200">
                    <div class="border-b border-slate-100 px-3 py-2">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                    </div>
                    <a href="{{ route('student.profile') }}" class="mt-1 block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Profile</a>
                    <a href="{{ route('student.settings') }}" class="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Settings</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full rounded-md px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50">Logout</button>
                    </form>
                </div>
            </details>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
            {{ $slot }}
        </main>

        <p class="pb-6 text-center text-xs text-slate-400">&copy; {{ date('Y') }} System Plus Computer College</p>
    </div>

    <script>
        // mobile sidebar
        (function () {
            var sidebar = document.getElementById('sidebar');
            var backdrop = document.getElementById('sidebar-backdrop');
            function open()  { sidebar.classList.remove('-translate-x-full'); backdrop.classList.remove('hidden'); }
            function close() { sidebar.classList.add('-translate-x-full'); backdrop.classList.add('hidden'); }
            document.getElementById('sidebar-open').addEventListener('click', open);
            backdrop.addEventListener('click', close);
            document.querySelectorAll('[data-sidebar-close]').forEach(function (b) { b.addEventListener('click', close); });
        })();
    </script>
    @stack('scripts')
</body>
</html>
