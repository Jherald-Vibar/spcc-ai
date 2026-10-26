<x-layouts.teacher :title="'Week '.$lesson->week.' – '.$lesson->title" active="subjects">
    <nav class="mb-4 flex flex-wrap items-center gap-1.5 text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('teacher.subjects') }}" class="hover:text-blue-700 hover:underline">My Subjects</a>
        <x-icon name="chevron-right" class="size-3.5" />
        <a href="{{ route('teacher.subjects', ['year' => $subject->year, 'subject' => $subject->code]) }}" class="hover:text-blue-700 hover:underline">{{ $subject->code }}</a>
        <x-icon name="chevron-right" class="size-3.5" />
        <span class="font-medium text-slate-700">Week {{ $lesson->week }}</span>
    </nav>

    <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-lg font-bold text-slate-900">Week {{ $lesson->week }} &ndash; {{ $lesson->title }}</h2>
            <x-badge :status="$lesson->display_status" />
            <div class="ml-auto flex gap-2">
                <a href="{{ route('teacher.lessons.edit', [$subject->code, $lesson->week]) }}"
                   class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Edit</a>
                <form method="POST" action="{{ route('teacher.lessons.publish', [$subject->code, $lesson->week]) }}">@csrf
                    <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ $lesson->isPublished() ? 'Move to draft' : 'Publish' }}</button>
                </form>
            </div>
        </div>
        <p class="mt-1 text-sm text-slate-500">{{ $subject->name }}</p>

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
                        @if ($file->processing_status === 'failed' && $file->processing_error)
                            <p class="mt-0.5 text-xs text-red-600">{{ $file->processing_error }}</p>
                        @endif
                    </div>
                    @php
                        $aiChip = match ($file->processing_status) {
                            'ready'      => ['AI ready · '.$file->chunk_count.' chunks', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
                            'processing' => ['Processing…', 'bg-blue-50 text-blue-700 ring-blue-200'],
                            'failed'     => ['AI failed', 'bg-red-50 text-red-700 ring-red-200'],
                            default      => ['Queued', 'bg-slate-100 text-slate-600 ring-slate-200'],
                        };
                    @endphp
                    <span class="hidden rounded-full px-2.5 py-1 text-xs font-medium ring-1 sm:inline-block {{ $aiChip[1] }}">{{ $aiChip[0] }}</span>
                    @if (in_array($file->processing_status, ['failed', 'pending'], true))
                        <form method="POST" action="{{ route('teacher.files.reprocess', $file) }}">@csrf
                            <button type="submit" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Retry AI</button>
                        </form>
                    @endif
                    <a href="{{ route('teacher.files.download', $file) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Download</a>
                </li>
            @empty
                <li class="px-4 py-6 text-center text-sm text-slate-500">No materials uploaded.</li>
            @endforelse
        </ul>
    </section>
</x-layouts.teacher>
