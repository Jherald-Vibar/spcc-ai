<x-layouts.admin title="Review Lesson" active="lessons">
    <nav class="mb-4 flex flex-wrap items-center gap-1.5 text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('admin.lessons') }}" class="hover:text-blue-700 hover:underline">Lessons</a>
        <x-icon name="chevron-right" class="size-3.5" />
        <span class="font-medium text-slate-700">{{ $lesson->subject->code }} &middot; Week {{ $lesson->week }}</span>
    </nav>

    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-lg font-bold text-slate-900">Week {{ $lesson->week }} &ndash; {{ $lesson->title }}</h2>
                <x-badge :status="$lesson->display_status" />
            </div>
            <p class="mt-1 text-sm text-slate-500">{{ $lesson->subject->name }} &middot; by {{ $lesson->creator->name ?? 'unknown' }}</p>

            <h3 class="mt-6 text-sm font-semibold text-slate-900">Description</h3>
            <p class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $lesson->description ?: 'No description.' }}</p>

            @if ($lesson->ai_prompt)
                <h3 class="mt-6 text-sm font-semibold text-slate-900">AI prompt</h3>
                <p class="mt-1 whitespace-pre-line rounded-lg bg-slate-50 p-3 text-sm text-slate-600 ring-1 ring-slate-200">{{ $lesson->ai_prompt }}</p>
            @endif

            <h3 class="mt-6 text-sm font-semibold text-slate-900">Materials</h3>
            <ul class="mt-2 divide-y divide-slate-100 rounded-lg ring-1 ring-slate-200">
                @forelse ($lesson->files as $file)
                    <li class="flex items-center gap-4 px-4 py-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-sky-100 text-xs font-bold text-sky-700">{{ $file->type }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $file->name }}</p>
                            <p class="text-xs text-slate-500">{{ $file->size }}</p>
                        </div>
                        <a href="{{ route('admin.files.download', $file) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Download</a>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-sm text-slate-500">No materials uploaded.</li>
                @endforelse
            </ul>
        </section>

        <aside class="space-y-6">
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="font-semibold text-slate-900">Actions</h3>
                <div class="mt-3 space-y-2">
                    @if ($lesson->isPublished())
                        <form method="POST" action="{{ route('admin.lessons.unpublish', $lesson) }}">@csrf
                            <button class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Unpublish</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.lessons.publish', $lesson) }}">@csrf
                            <button class="w-full rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Approve &amp; publish</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.lessons.destroy', $lesson) }}" onsubmit="return confirm('Remove this lesson and its files?')">@csrf @method('DELETE')
                        <button class="w-full rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Remove lesson</button>
                    </form>
                </div>
            </section>

            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="font-semibold text-slate-900">Reports</h3>
                <ul class="mt-3 space-y-3 text-sm">
                    @forelse ($lesson->reports as $r)
                        <li class="rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200">
                            <p class="text-slate-700">{{ $r->reason }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $r->reporter->name ?? 'Unknown' }} &middot; {{ $r->created_at->format('M j, g:i A') }} &middot; {{ $r->resolved_at ? 'resolved' : 'open' }}</p>
                        </li>
                    @empty
                        <li class="text-slate-500">No reports.</li>
                    @endforelse
                </ul>
                @if ($lesson->reports->whereNull('resolved_at')->isNotEmpty())
                    <form method="POST" action="{{ route('admin.lessons.dismiss', $lesson) }}" class="mt-3">@csrf
                        <button class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Mark reports resolved</button>
                    </form>
                @endif
            </section>
        </aside>
    </div>
</x-layouts.admin>
