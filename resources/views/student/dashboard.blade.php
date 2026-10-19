<x-layouts.student title="Dashboard" active="dashboard">
    <div class="rounded-2xl bg-gradient-to-r from-blue-900 to-blue-700 p-6 text-white shadow-sm sm:p-8">
        <p class="text-sm text-blue-200">Welcome back,</p>
        <h2 class="mt-1 text-2xl font-bold">{{ auth()->user()->name }}</h2>
        <p class="mt-2 text-blue-100">Learn Smarter. Achieve More.</p>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['Subjects', $subjectCount, 'book-open', 'bg-blue-100 text-blue-700'],
            ['Lessons completed', $completed, 'check', 'bg-green-100 text-green-700'],
            ['In progress', $inProgress, 'clock', 'bg-amber-100 text-amber-700'],
        ] as [$label, $value, $icon, $tone])
            <div class="flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <span class="grid size-11 place-items-center rounded-lg {{ $tone }}">
                    <x-icon :name="$icon" class="size-6" />
                </span>
                <div>
                    <p class="text-2xl font-bold text-slate-900">{{ $value }}</p>
                    <p class="text-sm text-slate-500">{{ $label }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <h3 class="font-semibold text-slate-900">Continue learning</h3>
            @if ($continue)
                <a href="{{ route('student.lessons.show', [$continueSubject['code'], $continue['week']]) }}"
                   class="mt-4 flex items-center gap-4 rounded-lg border border-slate-200 p-4 transition hover:border-blue-300 hover:bg-blue-50/50">
                    <span class="grid size-12 shrink-0 place-items-center rounded-lg bg-blue-700 text-white">
                        <x-icon name="play" class="size-6" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-slate-500">{{ $continueSubject['name'] }}</p>
                        <p class="truncate font-medium text-slate-900">Week {{ $continue['week'] }} &ndash; {{ $continue['title'] }}</p>
                    </div>
                    <x-student.status-badge status="in_progress" />
                    <x-icon name="chevron-right" class="size-5 text-slate-400" />
                </a>
            @else
                <p class="mt-4 text-sm text-slate-500">You have no lessons in progress. Pick one from My Lessons.</p>
            @endif
        </section>

        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h3 class="font-semibold text-slate-900">Quick links</h3>
            <div class="mt-4 space-y-2">
                <a href="{{ route('student.lessons') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <x-icon name="book-open" class="size-5 text-blue-700" /> Browse my lessons
                </a>
                <a href="{{ route('student.profile') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <x-icon name="user" class="size-5 text-blue-700" /> View profile
                </a>
            </div>
        </section>
    </div>
</x-layouts.student>
