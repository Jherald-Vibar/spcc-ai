<x-layouts.teacher title="My Subjects" active="subjects">
    <div class="grid gap-6 lg:grid-cols-[22rem_1fr]">

        {{-- 1. Select Year and Subject --}}
        <section class="h-fit rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="flex items-center gap-2 font-semibold text-slate-900">
                <span class="grid size-6 place-items-center rounded-full bg-blue-700 text-xs font-bold text-white">1</span>
                Select Year and Subject
            </h2>

            <form method="GET" action="{{ route('teacher.subjects') }}" class="mt-5 space-y-4">
                <div>
                    <label for="year" class="mb-1.5 block text-sm font-medium text-slate-700">Year Level</label>
                    <select id="year" name="year" required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20">
                        <option value="">Select year level</option>
                        @foreach ($years as $value => $label)
                            <option value="{{ $value }}" @selected($selectedYear === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="subject" class="mb-1.5 block text-sm font-medium text-slate-700">Subject</label>
                    <select id="subject" name="subject" required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20">
                        <option value="">Select subject</option>
                        @foreach ($subjects as $s)
                            <option value="{{ $s['code'] }}" data-year="{{ $s['year'] }}"
                                    @selected($selectedSubject && $selectedSubject['code'] === $s['code'])>
                                {{ $s['name'] }} ({{ $s['code'] }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit"
                        class="w-full rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2">
                    Enter
                </button>
            </form>
        </section>

        {{-- 2. Subject Dashboard (Teacher View) --}}
        <section class="min-w-0 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="flex items-center gap-2 font-semibold text-slate-900">
                    <span class="grid size-6 place-items-center rounded-full bg-blue-700 text-xs font-bold text-white">2</span>
                    Subject Dashboard
                </h2>
                @if ($selectedSubject)
                    <a href="{{ route('teacher.lessons.create', $selectedSubject['code']) }}"
                       class="ml-auto inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">
                        <x-icon name="plus" class="size-4" /> Create Lesson
                    </a>
                @endif
            </div>

            @if (! $selectedSubject)
                <div class="mt-6 rounded-lg border border-dashed border-slate-300 px-6 py-12 text-center">
                    <x-icon name="book-open" class="mx-auto size-10 text-slate-300" />
                    <p class="mt-3 text-sm text-slate-500">Choose a year level and subject, then press <strong>Enter</strong> to manage its lessons.</p>
                </div>
            @else
                <p class="mt-3 text-sm font-medium text-slate-600">
                    {{ $selectedSubject['code'] }} &ndash; {{ $years[$selectedSubject['year']] }}
                    <span class="font-normal text-slate-400">&middot; {{ $selectedSubject['name'] }}</span>
                </p>

                {{-- Tabs --}}
                <div class="mt-4 flex gap-6 border-b border-slate-200" role="tablist">
                    <button type="button" role="tab" data-tab="lessons" aria-selected="true"
                            class="-mb-px border-b-2 border-blue-700 px-1 pb-3 text-sm font-medium text-blue-700">Lessons</button>
                    <button type="button" role="tab" data-tab="analytics" aria-selected="false"
                            class="-mb-px border-b-2 border-transparent px-1 pb-3 text-sm font-medium text-slate-500 hover:text-slate-800">Analytics</button>
                </div>

                <div data-panel="lessons" class="mt-4">
                    @forelse ($lessons as $lesson)
                        @if ($loop->first) <ul class="space-y-2"> @endif
                        <li class="flex items-center gap-4 rounded-lg border border-slate-200 px-4 py-3">
                            <x-icon name="document" class="size-5 shrink-0 text-blue-600" />
                            <span class="w-16 shrink-0 text-sm font-semibold text-slate-700">Week {{ $lesson['week'] }}</span>
                            <span class="min-w-0 flex-1 truncate text-sm text-slate-800">{{ $lesson['title'] }}</span>
                            <x-badge :status="$lesson['status']" />

                            <details class="relative">
                                <summary class="grid size-8 cursor-pointer list-none place-items-center rounded-md text-slate-500 hover:bg-slate-100 [&::-webkit-details-marker]:hidden" aria-label="Lesson actions">
                                    <x-icon name="ellipsis-vertical" class="size-5" />
                                </summary>
                                <div class="absolute right-0 z-10 mt-1 w-36 rounded-lg bg-white p-1 shadow-lg ring-1 ring-slate-200">
                                    {{-- TODO: point these at real lesson edit / preview / delete routes --}}
                                    <a href="#" class="block rounded-md px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">Edit</a>
                                    <a href="#" class="block rounded-md px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">Preview</a>
                                    <a href="#" class="block rounded-md px-3 py-1.5 text-sm text-red-600 hover:bg-red-50">Delete</a>
                                </div>
                            </details>
                        </li>
                        @if ($loop->last) </ul> @endif
                    @empty
                        <div class="rounded-lg border border-dashed border-slate-300 px-6 py-12 text-center text-sm text-slate-500">
                            No lessons yet. Use <strong>Create Lesson</strong> to add the first one.
                        </div>
                    @endforelse
                </div>

                <div data-panel="analytics" class="mt-4 hidden">
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($analytics as $stat)
                            <div class="rounded-lg bg-slate-50 p-4 ring-1 ring-slate-200">
                                <p class="text-2xl font-bold text-slate-900">{{ $stat['value'] }}</p>
                                <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs text-slate-400">Sample figures until analytics are connected.</p>
                </div>
            @endif
        </section>
    </div>

    @push('scripts')
        <script>
            (function () {
                // subject list follows the selected year level
                var year = document.getElementById('year');
                var subject = document.getElementById('subject');
                var options = Array.prototype.slice.call(subject.querySelectorAll('option[data-year]'));

                function filter() {
                    options.forEach(function (o) {
                        var match = !year.value || o.dataset.year === year.value;
                        o.hidden = !match;
                        o.disabled = !match;
                    });
                    if (subject.selectedOptions[0] && subject.selectedOptions[0].disabled) subject.value = '';
                }
                year.addEventListener('change', filter);
                filter();

                // Lessons / Analytics tabs
                var tabs = document.querySelectorAll('[data-tab]');
                var panels = document.querySelectorAll('[data-panel]');
                tabs.forEach(function (t) {
                    t.addEventListener('click', function () {
                        tabs.forEach(function (o) {
                            var on = o === t;
                            o.setAttribute('aria-selected', on ? 'true' : 'false');
                            ['border-blue-700', 'text-blue-700'].forEach(function (c) { o.classList.toggle(c, on); });
                            ['border-transparent', 'text-slate-500', 'hover:text-slate-800'].forEach(function (c) { o.classList.toggle(c, !on); });
                        });
                        panels.forEach(function (p) { p.classList.toggle('hidden', p.dataset.panel !== t.dataset.tab); });
                    });
                });
            })();
        </script>
    @endpush
</x-layouts.teacher>
