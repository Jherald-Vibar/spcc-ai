<div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
    @include('student.lessons.partials.player')

    <div>
        <h3 class="font-semibold text-slate-900">Lesson Overview</h3>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $lesson['description'] }}</p>

        <div class="mt-5 space-y-2">
            <button type="button" data-tab-trigger="video"
                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">
                <x-icon name="play" class="size-4" /> Watch AI Video
            </button>
            <button type="button" data-tab-trigger="chat"
                    class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <x-icon name="document" class="size-4" /> View Summary
            </button>
        </div>
    </div>
</div>
