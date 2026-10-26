<x-layouts.teacher title="Dashboard" active="dashboard">
    <div class="rounded-2xl bg-gradient-to-r from-blue-900 to-blue-700 p-6 text-white shadow-sm sm:p-8">
        <p class="text-sm text-blue-200">Welcome back,</p>
        <h2 class="mt-1 text-2xl font-bold">{{ auth()->user()->name }}</h2>
        <p class="mt-2 text-blue-100">Create lessons, upload materials and publish AI videos for your students.</p>
        @if ($subject)
            <a href="{{ route('teacher.lessons.create', $subject->code) }}"
               class="mt-5 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-blue-800 shadow-sm transition hover:bg-blue-50">
                <x-icon name="plus" class="size-4" /> Create Lesson
            </a>
        @else
            <p class="mt-4 rounded-lg bg-white/10 px-4 py-2 text-sm text-blue-100">No subject is assigned to you yet. Ask an administrator to assign one.</p>
        @endif
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['My subjects', $subjectCount, 'book-open', 'bg-blue-100 text-blue-700'],
            ['Published lessons', $published, 'check', 'bg-green-100 text-green-700'],
            ['Drafts', $drafts, 'document', 'bg-orange-100 text-orange-700'],
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

    <section class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-slate-900">Recent lessons</h3>
            <a href="{{ route('teacher.subjects') }}" class="text-sm font-medium text-blue-700 hover:underline">View all</a>
        </div>
        <ul class="mt-4 divide-y divide-slate-100">
            @forelse ($recent as $lesson)
                <li>
                    <a href="{{ route('teacher.lessons.show', [$lesson->subject->code, $lesson->week]) }}" class="flex items-center gap-3 py-3 hover:bg-slate-50/60">
                        <span class="w-24 text-sm font-semibold text-blue-700">{{ $lesson->subject->code }} &middot; W{{ $lesson->week }}</span>
                        <span class="flex-1 truncate text-sm text-slate-800">{{ $lesson->title }}</span>
                        <x-badge :status="$lesson->display_status" />
                    </a>
                </li>
            @empty
                <li class="py-6 text-center text-sm text-slate-500">No lessons yet.</li>
            @endforelse
        </ul>
    </section>
</x-layouts.teacher>
