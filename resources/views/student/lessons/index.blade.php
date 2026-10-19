<x-layouts.student title="My Lessons" active="lessons">
    <div class="grid gap-6 lg:grid-cols-[22rem_1fr]">

        {{-- 1. Select Year and Subject --}}
        <section class="h-fit rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="flex items-center gap-2 font-semibold text-slate-900">
                <span class="grid size-6 place-items-center rounded-full bg-blue-700 text-xs font-bold text-white">1</span>
                Select Year and Subject
            </h2>

            <form method="GET" action="{{ route('student.lessons') }}" class="mt-5 space-y-4">
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

        {{-- 2. Available Lessons --}}
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="flex items-center gap-2 font-semibold text-slate-900">
                <span class="grid size-6 place-items-center rounded-full bg-blue-700 text-xs font-bold text-white">2</span>
                Available Lessons
            </h2>

            @if (! $selectedSubject)
                <div class="mt-6 rounded-lg border border-dashed border-slate-300 px-6 py-12 text-center">
                    <x-icon name="book-open" class="mx-auto size-10 text-slate-300" />
                    <p class="mt-3 text-sm text-slate-500">Choose your year level and subject, then press <strong>Enter</strong> to see the lessons.</p>
                </div>
            @else
                <p class="mt-3 text-sm font-medium text-slate-600">
                    {{ $selectedSubject['code'] }} &ndash; {{ $years[$selectedSubject['year']] }}
                    <span class="font-normal text-slate-400">&middot; {{ $selectedSubject['name'] }}</span>
                </p>

                @forelse ($lessons as $lesson)
                    @if ($loop->first) <ul class="mt-4 space-y-2"> @endif
                    <li>
                        <a href="{{ route('student.lessons.show', [$selectedSubject['code'], $lesson['week']]) }}"
                           class="flex items-center gap-4 rounded-lg border border-slate-200 px-4 py-3 transition hover:border-blue-300 hover:bg-blue-50/60">
                            <span class="w-16 shrink-0 text-sm font-semibold text-blue-700">Week {{ $lesson['week'] }}</span>
                            <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-800">{{ $lesson['title'] }}</span>
                            <x-student.status-badge :status="$lesson['status']" class="hidden sm:inline-flex" />
                            <x-icon name="chevron-right" class="size-4 shrink-0 text-slate-400" />
                        </a>
                    </li>
                    @if ($loop->last) </ul> @endif
                @empty
                    <div class="mt-6 rounded-lg border border-dashed border-slate-300 px-6 py-12 text-center text-sm text-slate-500">
                        No lessons have been published for this subject yet.
                    </div>
                @endforelse
            @endif
        </section>
    </div>

    @push('scripts')
        <script>
            // show only the subjects that belong to the selected year level
            (function () {
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
            })();
        </script>
    @endpush
</x-layouts.student>
