@php
    $tabs = [
        'overview'  => 'Overview',
        'video'     => 'AI Video',
        'chat'      => 'AI Chat',
        'reviewer'  => 'Reviewer',
        'materials' => 'Materials',
        'forum'     => 'Forum',
    ];

    $tools = [
        ['chat',      'AI Chat',   'Ask lesson-related questions', 'chat',            'bg-orange-100 text-orange-600'],
        ['reviewer',  'Reviewer',  'Practice questions (MCQ, ID, etc.)', 'clipboard-check', 'bg-green-100 text-green-600'],
        ['materials', 'Materials', 'View / Download files',        'folder',          'bg-sky-100 text-sky-600'],
        ['forum',     'Forum',     'Discuss with classmates',      'users',           'bg-violet-100 text-violet-600'],
    ];
@endphp

<x-layouts.student :title="'Week '.$lesson['week'].' – '.$lesson['title']" active="lessons">
    {{-- Breadcrumb --}}
    <nav class="mb-4 flex flex-wrap items-center gap-1.5 text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('student.lessons') }}" class="hover:text-blue-700 hover:underline">My Lessons</a>
        <x-icon name="chevron-right" class="size-3.5" />
        <a href="{{ route('student.lessons', ['year' => $subject['year'], 'subject' => $subject['code']]) }}" class="hover:text-blue-700 hover:underline">{{ $subject['code'] }}</a>
        <x-icon name="chevron-right" class="size-3.5" />
        <span class="font-medium text-slate-700">Week {{ $lesson['week'] }}</span>
    </nav>

    <div class="grid gap-6 xl:grid-cols-[1fr_18rem]">

        {{-- Lesson dashboard --}}
        <section class="min-w-0 rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-center gap-3 px-6 pt-5">
                <x-brand-logo size="size-9" />
                <div class="min-w-0 flex-1">
                    <h2 class="truncate text-lg font-bold text-slate-900">Week {{ $lesson['week'] }} &ndash; {{ $lesson['title'] }}</h2>
                    <p class="truncate text-xs text-slate-500">{{ $subject['name'] }}</p>
                </div>
                <x-student.status-badge :status="$lesson['status']" />
            </div>

            {{-- Tabs --}}
            <div class="mt-4 overflow-x-auto border-b border-slate-200 px-6">
                <div class="flex min-w-max gap-6" role="tablist">
                    @foreach ($tabs as $key => $label)
                        <button type="button" role="tab" data-tab="{{ $key }}"
                                aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                                class="-mb-px border-b-2 px-1 pb-3 text-sm font-medium transition
                                       {{ $tab === $key ? 'border-blue-700 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="p-6">
                <div data-panel="overview" class="{{ $tab === 'overview' ? '' : 'hidden' }}">@include('student.lessons.partials.overview')</div>
                <div data-panel="video"    class="{{ $tab === 'video' ? '' : 'hidden' }}">@include('student.lessons.partials.video')</div>
                <div data-panel="chat"     class="{{ $tab === 'chat' ? '' : 'hidden' }}">@include('student.lessons.partials.chat')</div>
                <div data-panel="reviewer" class="{{ $tab === 'reviewer' ? '' : 'hidden' }}">@include('student.lessons.partials.reviewer')</div>
                <div data-panel="materials" class="{{ $tab === 'materials' ? '' : 'hidden' }}">@include('student.lessons.partials.materials')</div>
                <div data-panel="forum"    class="{{ $tab === 'forum' ? '' : 'hidden' }}">@include('student.lessons.partials.forum')</div>
            </div>
        </section>

        {{-- Quick tools --}}
        <aside class="h-fit rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h3 class="font-semibold text-slate-900">Quick Tools</h3>
            <div class="mt-4 space-y-2">
                @foreach ($tools as [$target, $name, $hint, $icon, $tone])
                    <button type="button" data-tab-trigger="{{ $target }}"
                            class="flex w-full items-center gap-3 rounded-lg border border-slate-200 p-3 text-left transition hover:border-blue-300 hover:bg-blue-50/50">
                        <span class="grid size-10 shrink-0 place-items-center rounded-lg {{ $tone }}">
                            <x-icon :name="$icon" class="size-5" />
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-slate-900">{{ $name }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $hint }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
        </aside>
    </div>

    @push('scripts')
        <script>
            (function () {
                var tabs = document.querySelectorAll('[data-tab]');
                var panels = document.querySelectorAll('[data-panel]');
                var on = ['border-blue-700', 'text-blue-700'];
                var off = ['border-transparent', 'text-slate-500', 'hover:text-slate-800'];

                function show(key) {
                    tabs.forEach(function (t) {
                        var active = t.dataset.tab === key;
                        t.setAttribute('aria-selected', active ? 'true' : 'false');
                        on.forEach(function (c) { t.classList.toggle(c, active); });
                        off.forEach(function (c) { t.classList.toggle(c, !active); });
                    });
                    panels.forEach(function (p) { p.classList.toggle('hidden', p.dataset.panel !== key); });
                    history.replaceState(null, '', '?tab=' + key);
                }

                tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.dataset.tab); }); });
                document.querySelectorAll('[data-tab-trigger]').forEach(function (b) {
                    b.addEventListener('click', function () {
                        show(b.dataset.tabTrigger);
                        document.querySelector('[data-tab="' + b.dataset.tabTrigger + '"]').scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    });
                });
            })();
        </script>
    @endpush
</x-layouts.student>
