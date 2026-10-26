@php
    $field = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20';
    $menuItem = 'block w-full rounded-md px-3 py-1.5 text-left text-sm text-slate-700 hover:bg-slate-50';
@endphp

<x-layouts.admin title="Subjects" active="subjects">
    <section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-wrap items-center gap-3 px-6 pt-5">
            <h2 class="font-semibold text-slate-900">Subjects</h2>
            <button type="button" id="add-subject-btn"
                    class="ml-auto inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">
                <x-icon name="plus" class="size-4" /> Add Subject
            </button>
        </div>
        <p class="px-6 pt-1 text-sm text-slate-500">Create the subjects and assign a teacher. Teachers only see the subjects assigned to them.</p>

        <form method="GET" action="{{ route('admin.subjects') }}" class="flex items-center gap-3 px-6 py-4">
            <select name="year" onchange="this.form.submit()" class="{{ $field }} !w-auto" aria-label="Filter by year level">
                <option value="">All year levels</option>
                @foreach ($years as $value => $label)
                    <option value="{{ $value }}" @selected($year === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[44rem] text-left text-sm">
                <thead class="border-y border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Code</th>
                        <th class="px-6 py-3 font-medium">Subject</th>
                        <th class="px-6 py-3 font-medium">Year</th>
                        <th class="px-6 py-3 font-medium">Teacher</th>
                        <th class="px-6 py-3 font-medium">Lessons</th>
                        <th class="px-6 py-3 text-right font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($subjects as $s)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-6 py-3 font-semibold text-slate-900">{{ $s->code }}</td>
                            <td class="px-6 py-3 text-slate-700">{{ $s->name }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $years[$s->year] ?? $s->year }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $s->teacher->name ?? 'Unassigned' }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $s->lessons_count }}</td>
                            <td class="px-6 py-3 text-right">
                                <details class="relative inline-block text-left">
                                    <summary class="grid size-8 cursor-pointer list-none place-items-center rounded-md text-slate-500 hover:bg-slate-100 [&::-webkit-details-marker]:hidden" aria-label="Subject actions">
                                        <x-icon name="ellipsis-vertical" class="size-5" />
                                    </summary>
                                    <div class="absolute right-0 z-10 mt-1 w-40 rounded-lg bg-white p-1 shadow-lg ring-1 ring-slate-200">
                                        <button type="button" class="{{ $menuItem }}" data-edit-subject
                                                data-action="{{ route('admin.subjects.update', $s) }}"
                                                data-subject="{{ json_encode(['code' => $s->code, 'name' => $s->name, 'year' => $s->year, 'teacher_id' => $s->teacher_id]) }}">Edit</button>
                                        <form method="POST" action="{{ route('admin.subjects.destroy', $s) }}" onsubmit="return confirm('Delete subject {{ $s->code }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="block w-full rounded-md px-3 py-1.5 text-left text-sm text-red-600 hover:bg-red-50">Delete</button>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">No subjects yet. Use <strong>Add Subject</strong> to create the first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- One dialog serves both Add and Edit --}}
    <dialog id="subject-dialog" class="m-auto w-full max-w-md rounded-2xl p-0 shadow-2xl backdrop:bg-slate-900/50">
        <form method="POST" action="{{ route('admin.subjects.store') }}" id="subject-form" class="p-6">
            @csrf
            <span id="subject-method"></span>
            <input type="hidden" name="_form" value="subject">
            <h3 id="subject-title" class="text-lg font-semibold text-slate-900">Add Subject</h3>

            <div class="mt-5 space-y-4">
                <div>
                    <label for="s-code" class="mb-1.5 block text-sm font-medium text-slate-700">Code</label>
                    <input id="s-code" name="code" type="text" required maxlength="20" value="{{ old('_form') === 'subject' ? old('code') : '' }}" class="{{ $field }} uppercase" placeholder="e.g. IAS2">
                </div>
                <div>
                    <label for="s-name" class="mb-1.5 block text-sm font-medium text-slate-700">Subject name</label>
                    <input id="s-name" name="name" type="text" required maxlength="150" value="{{ old('_form') === 'subject' ? old('name') : '' }}" class="{{ $field }}" placeholder="Information Assurance and Security 2">
                </div>
                <div>
                    <label for="s-year" class="mb-1.5 block text-sm font-medium text-slate-700">Year level</label>
                    <select id="s-year" name="year" required class="{{ $field }}">
                        @foreach ($years as $value => $label)
                            <option value="{{ $value }}" @selected((int) (old('_form') === 'subject' ? old('year') : 4) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="s-teacher" class="mb-1.5 block text-sm font-medium text-slate-700">Teacher</label>
                    <select id="s-teacher" name="teacher_id" class="{{ $field }}">
                        <option value="">Unassigned</option>
                        @foreach ($teachers as $t)
                            <option value="{{ $t->id }}" @selected((int) (old('_form') === 'subject' ? old('teacher_id') : 0) === $t->id)>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" id="subject-cancel" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Save</button>
            </div>
        </form>
    </dialog>

    @push('scripts')
        <script>
            (function () {
                var dlg = document.getElementById('subject-dialog');
                var form = document.getElementById('subject-form');
                var storeUrl = form.action;

                function open(mode, btn) {
                    var edit = mode === 'edit';
                    document.getElementById('subject-title').textContent = edit ? 'Edit Subject' : 'Add Subject';
                    document.getElementById('subject-method').innerHTML = edit ? '<input type="hidden" name="_method" value="PATCH">' : '';
                    form.action = edit ? btn.dataset.action : storeUrl;
                    if (edit) {
                        var s = JSON.parse(btn.dataset.subject);
                        document.getElementById('s-code').value = s.code;
                        document.getElementById('s-name').value = s.name;
                        document.getElementById('s-year').value = s.year;
                        document.getElementById('s-teacher').value = s.teacher_id || '';
                        btn.closest('details').removeAttribute('open');
                    }
                    dlg.showModal();
                }

                document.getElementById('add-subject-btn').addEventListener('click', function () { open('add'); });
                document.querySelectorAll('[data-edit-subject]').forEach(function (b) {
                    b.addEventListener('click', function () { open('edit', b); });
                });
                document.getElementById('subject-cancel').addEventListener('click', function () { dlg.close(); });
                dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });

                @if (old('_form') === 'subject' && $errors->any()) dlg.showModal(); @endif
            })();
        </script>
    @endpush
</x-layouts.admin>
