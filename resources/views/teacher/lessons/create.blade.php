@php
    $steps = [
        1 => ['Lesson Details',   'Set week number, title, and description.'],
        2 => ['Upload Materials', 'Upload PDF, PPTX, or DOCX files.'],
        3 => ['Enter AI Prompt',  'Provide a prompt for the AI video.'],
        4 => ['Generate Video',   'Let the AI create the lesson video.'],
        5 => ['Review & Publish', 'Preview and publish for students.'],
    ];
    $field = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20';
    $primary = 'rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2';
    $secondary = 'rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50';
@endphp

<x-layouts.teacher title="Create Lesson" active="subjects">
    <nav class="mb-4 flex flex-wrap items-center gap-1.5 text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('teacher.subjects') }}" class="hover:text-blue-700 hover:underline">My Subjects</a>
        <x-icon name="chevron-right" class="size-3.5" />
        <a href="{{ route('teacher.subjects', ['year' => $subject['year'], 'subject' => $subject['code']]) }}" class="hover:text-blue-700 hover:underline">{{ $subject['code'] }}</a>
        <x-icon name="chevron-right" class="size-3.5" />
        <span class="font-medium text-slate-700">Create Lesson</span>
    </nav>

    <form id="lesson-form" method="POST" enctype="multipart/form-data" novalidate
          action="{{ route('teacher.lessons.store', $subject['code']) }}"
          class="grid gap-6 lg:grid-cols-[20rem_1fr]">
        @csrf
        <input type="hidden" name="generate_video" id="generate_video" value="0">

        {{-- Step list --}}
        <aside class="h-fit rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold text-slate-900">Create Lesson <span class="font-normal text-slate-400">(Step-by-Step)</span></h2>
            <ol class="mt-4 space-y-1">
                @foreach ($steps as $n => [$name, $hint])
                    <li>
                        <button type="button" data-goto="{{ $n }}"
                                class="flex w-full items-start gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-slate-50">
                            <span data-bullet="{{ $n }}" class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-slate-200 text-xs font-bold text-slate-600">{{ $n }}</span>
                            <span>
                                <span class="block text-sm font-semibold text-slate-900">{{ $name }}</span>
                                <span class="block text-xs text-slate-500">{{ $hint }}</span>
                            </span>
                        </button>
                    </li>
                @endforeach
            </ol>
        </aside>

        {{-- Step panels --}}
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ $subject['code'] }} &middot; {{ $subject['name'] }}</p>

            {{-- Step 1 --}}
            <div data-step="1">
                <h3 class="mt-1 text-lg font-semibold text-slate-900">Step 1: Lesson Details</h3>
                <div class="mt-5 space-y-4">
                    <div>
                        <label for="week" class="mb-1.5 block text-sm font-medium text-slate-700">Week Number</label>
                        <select id="week" name="week" class="{{ $field }}">
                            @for ($w = 1; $w <= 18; $w++)
                                <option value="{{ $w }}" @selected((int) old('week', $nextWeek) === $w)>Week {{ $w }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label for="title" class="mb-1.5 block text-sm font-medium text-slate-700">Lesson Title</label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}" maxlength="150" class="{{ $field }}" placeholder="e.g. Polyalphabetic Ciphers">
                        <p id="title-error" class="mt-1.5 hidden text-sm text-red-600">Please enter a lesson title.</p>
                    </div>
                    <div>
                        <label for="description" class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
                        <textarea id="description" name="description" rows="4" maxlength="2000" class="{{ $field }}" placeholder="What will students learn?">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Step 2 --}}
            <div data-step="2" class="hidden">
                <h3 class="mt-1 text-lg font-semibold text-slate-900">Step 2: Upload Materials</h3>
                <p class="mt-1 text-sm text-slate-500">PDF, PPTX or DOCX, up to 20 MB each. The AI uses these to build the lesson.</p>

                <label for="materials" class="mt-5 flex cursor-pointer flex-col items-center rounded-xl border-2 border-dashed border-slate-300 px-6 py-10 text-center transition hover:border-blue-400 hover:bg-blue-50/40">
                    <x-icon name="cloud-upload" class="size-10 text-blue-600" />
                    <span class="mt-2 text-sm font-medium text-slate-700">Click to choose files</span>
                    <span class="text-xs text-slate-400">.pdf, .pptx, .docx</span>
                    <input id="materials" name="materials[]" type="file" multiple accept=".pdf,.pptx,.docx" class="sr-only">
                </label>
                <ul id="file-list" class="mt-3 space-y-1 text-sm text-slate-600"></ul>
            </div>

            {{-- Step 3 --}}
            <div data-step="3" class="hidden">
                <h3 class="mt-1 text-lg font-semibold text-slate-900">Step 3: Enter AI Prompt</h3>
                <p class="mt-1 text-sm text-slate-500">Tell the AI how the video should explain this lesson.</p>
                <textarea id="ai_prompt" name="ai_prompt" rows="6" maxlength="4000" class="{{ $field }} mt-5"
                          placeholder="e.g. Explain the Vigenere cipher step by step with a worked example, in simple language for 4th-year students.">{{ old('ai_prompt') }}</textarea>
            </div>

            {{-- Step 4 --}}
            <div data-step="4" class="hidden">
                <h3 class="mt-1 text-lg font-semibold text-slate-900">Step 4: Generate Video</h3>
                <p class="mt-1 text-sm text-slate-500">The AI video is created from your materials and prompt.</p>

                <div class="mt-5 rounded-xl bg-slate-50 p-6 text-center ring-1 ring-slate-200">
                    <x-icon name="sparkles" class="mx-auto size-10 text-blue-600" />
                    <button type="button" id="generate-btn" class="{{ $primary }} mt-4">Generate AI Video</button>
                    <p id="generate-status" class="mt-3 text-sm text-slate-500">Video generation has not been requested.</p>
                </div>
            </div>

            {{-- Step 5 --}}
            <div data-step="5" class="hidden">
                <h3 class="mt-1 text-lg font-semibold text-slate-900">Step 5: Review &amp; Publish</h3>
                <dl class="mt-5 divide-y divide-slate-100 rounded-lg ring-1 ring-slate-200 text-sm">
                    <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-slate-500">Week</dt><dd id="r-week" class="font-medium text-slate-900"></dd></div>
                    <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-slate-500">Title</dt><dd id="r-title" class="font-medium text-slate-900"></dd></div>
                    <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-slate-500">Materials</dt><dd id="r-files" class="font-medium text-slate-900"></dd></div>
                    <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-slate-500">AI prompt</dt><dd id="r-prompt" class="max-w-xs truncate font-medium text-slate-900"></dd></div>
                    <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-slate-500">AI video</dt><dd id="r-video" class="font-medium text-slate-900"></dd></div>
                </dl>
            </div>

            {{-- Footer buttons --}}
            <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
                <a href="{{ route('teacher.subjects', ['year' => $subject['year'], 'subject' => $subject['code']]) }}" id="cancel-btn" class="{{ $secondary }}">Cancel</a>
                <button type="button" id="back-btn" class="{{ $secondary }} hidden">Back</button>

                <div class="ml-auto flex flex-wrap gap-3">
                    <button type="button" id="next-btn" class="{{ $primary }}">Next &rarr;</button>
                    <button type="submit" name="action" value="draft" id="draft-btn" class="{{ $secondary }} hidden">Save as Draft</button>
                    <button type="submit" name="action" value="publish" id="publish-btn" class="{{ $primary }} hidden">Publish</button>
                </div>
            </div>
        </section>
    </form>

    @push('scripts')
        <script>
            (function () {
                var total = 5, current = 1;
                var $ = function (id) { return document.getElementById(id); };
                var panels = document.querySelectorAll('[data-step]');

                function render() {
                    panels.forEach(function (p) { p.classList.toggle('hidden', +p.dataset.step !== current); });

                    document.querySelectorAll('[data-bullet]').forEach(function (b) {
                        var n = +b.dataset.bullet;
                        b.className = 'mt-0.5 grid size-6 shrink-0 place-items-center rounded-full text-xs font-bold ' +
                            (n === current ? 'bg-blue-700 text-white' : n < current ? 'bg-green-500 text-white' : 'bg-slate-200 text-slate-600');
                    });
                    document.querySelectorAll('[data-goto]').forEach(function (b) {
                        b.classList.toggle('bg-blue-50', +b.dataset.goto === current);
                    });

                    $('back-btn').classList.toggle('hidden', current === 1);
                    $('cancel-btn').classList.toggle('hidden', current !== 1);
                    $('next-btn').classList.toggle('hidden', current === total);
                    $('draft-btn').classList.toggle('hidden', current !== total);
                    $('publish-btn').classList.toggle('hidden', current !== total);

                    if (current === total) summarize();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }

                function titleOk() {
                    var ok = $('title').value.trim() !== '';
                    $('title-error').classList.toggle('hidden', ok);
                    if (!ok) $('title').focus();
                    return ok;
                }

                function go(n) {
                    if (n > 1 && !$('title').value.trim()) { current = 1; render(); titleOk(); return; }
                    current = Math.max(1, Math.min(total, n));
                    render();
                }

                function summarize() {
                    var files = $('materials').files.length;
                    $('r-week').textContent = 'Week ' + $('week').value;
                    $('r-title').textContent = $('title').value.trim() || '(none)';
                    $('r-files').textContent = files ? files + ' file' + (files > 1 ? 's' : '') : 'None';
                    $('r-prompt').textContent = $('ai_prompt').value.trim() || '(none)';
                    $('r-video').textContent = $('generate_video').value === '1' ? 'Will be generated' : 'Not requested';
                }

                $('next-btn').addEventListener('click', function () {
                    if (current === 1 && !titleOk()) return;
                    go(current + 1);
                });
                $('back-btn').addEventListener('click', function () { go(current - 1); });
                document.querySelectorAll('[data-goto]').forEach(function (b) {
                    b.addEventListener('click', function () { go(+b.dataset.goto); });
                });

                $('materials').addEventListener('change', function () {
                    var list = $('file-list');
                    list.innerHTML = '';
                    Array.prototype.forEach.call(this.files, function (f) {
                        var li = document.createElement('li');
                        li.textContent = f.name + ' (' + Math.max(1, Math.round(f.size / 1024)) + ' KB)'; // textContent: file names are untrusted
                        list.appendChild(li);
                    });
                });

                $('generate-btn').addEventListener('click', function () {
                    $('generate_video').value = '1';
                    $('generate-status').textContent = 'Video generation requested. It will start when you save or publish this lesson.';
                    this.textContent = 'Video requested';
                    this.disabled = true;
                    this.classList.add('opacity-60');
                });

                // errors come back from the server: reopen step 1
                @if ($errors->any()) current = 1; @endif
                render();
            })();
        </script>
    @endpush
</x-layouts.teacher>
