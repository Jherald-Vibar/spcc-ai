@php
    $tabs = ['students' => 'Students', 'teachers' => 'Teachers', 'administrators' => 'Administrators'];
    $field = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20';
@endphp

<x-layouts.admin title="User Management" active="users">
    <section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-wrap items-center gap-3 px-6 pt-5">
            <h2 class="font-semibold text-slate-900">User Management</h2>
            <button type="button" id="add-user-btn"
                    class="ml-auto inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">
                <x-icon name="plus" class="size-4" /> Add User
            </button>
        </div>

        {{-- Tabs --}}
        <div class="mt-3 overflow-x-auto border-b border-slate-200 px-6">
            <div class="flex min-w-max gap-6">
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('admin.users', ['role' => $key]) }}"
                       @if ($tab === $key) aria-current="page" @endif
                       class="-mb-px border-b-2 px-1 pb-3 text-sm font-medium transition
                              {{ $tab === $key ? 'border-blue-700 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                        {{ $label }} <span class="ml-1 rounded-full bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500">{{ $counts[$key] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Search + filter --}}
        <form method="GET" action="{{ route('admin.users') }}" class="flex flex-wrap items-center gap-3 px-6 py-4">
            <input type="hidden" name="role" value="{{ $tab }}">
            <div class="relative min-w-0 flex-1 sm:max-w-sm">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                <input type="search" name="q" value="{{ $q }}" placeholder="Search users..." class="{{ $field }} pl-9">
            </div>
            <select name="status" onchange="this.form.submit()" class="{{ $field }} !w-auto" aria-label="Filter by status">
                <option value="">All statuses</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
            </select>
            <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Filter</button>
        </form>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[40rem] text-left text-sm">
                <thead class="border-y border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Name</th>
                        <th class="px-6 py-3 font-medium">Email</th>
                        <th class="px-6 py-3 font-medium">Role</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 text-right font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $u)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-6 py-3 font-medium text-slate-900">{{ $u['name'] }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $u['email'] }}</td>
                            <td class="px-6 py-3 capitalize text-slate-600">{{ $u['role'] }}</td>
                            <td class="px-6 py-3"><x-badge :status="$u['status']" /></td>
                            <td class="px-6 py-3 text-right">
                                <details class="relative inline-block text-left">
                                    <summary class="grid size-8 cursor-pointer list-none place-items-center rounded-md text-slate-500 hover:bg-slate-100 [&::-webkit-details-marker]:hidden" aria-label="User actions">
                                        <x-icon name="ellipsis-vertical" class="size-5" />
                                    </summary>
                                    <div class="absolute right-0 z-10 mt-1 w-40 rounded-lg bg-white p-1 shadow-lg ring-1 ring-slate-200">
                                        {{-- TODO: wire these to real edit / activate / delete routes --}}
                                        <a href="#" class="block rounded-md px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">Edit</a>
                                        <a href="#" class="block rounded-md px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">{{ $u['status'] === 'active' ? 'Deactivate' : 'Activate' }}</a>
                                        <a href="#" class="block rounded-md px-3 py-1.5 text-sm text-red-600 hover:bg-red-50">Delete</a>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">No users match your search.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Add user dialog --}}
    <dialog id="add-user" class="m-auto w-full max-w-md rounded-2xl p-0 shadow-2xl backdrop:bg-slate-900/50">
        <form method="POST" action="{{ route('admin.users.store') }}" class="p-6">
            @csrf
            <h3 class="text-lg font-semibold text-slate-900">Add User</h3>
            <p class="mt-1 text-sm text-slate-500">Only SPCC accounts are allowed.</p>

            <div class="mt-5 space-y-4">
                <div>
                    <label for="u-name" class="mb-1.5 block text-sm font-medium text-slate-700">Full name</label>
                    <input id="u-name" name="name" type="text" required value="{{ old('name') }}" class="{{ $field }}" placeholder="Surname, Given name">
                </div>
                <div>
                    <label for="u-email" class="mb-1.5 block text-sm font-medium text-slate-700">SPCC email</label>
                    <input id="u-email" name="email" type="email" required value="{{ old('email') }}" class="{{ $field }}" placeholder="Surname_name@spcc.edu.ph">
                </div>
                <div>
                    <label for="u-role" class="mb-1.5 block text-sm font-medium text-slate-700">Role</label>
                    <select id="u-role" name="role" class="{{ $field }}">
                        <option value="student" @selected(old('role') === 'student')>Student</option>
                        <option value="teacher" @selected(old('role') === 'teacher')>Teacher</option>
                        <option value="administrator" @selected(old('role') === 'administrator')>Administrator</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" id="add-user-cancel" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Add User</button>
            </div>
        </form>
    </dialog>

    @push('scripts')
        <script>
            (function () {
                var dlg = document.getElementById('add-user');
                document.getElementById('add-user-btn').addEventListener('click', function () { dlg.showModal(); });
                document.getElementById('add-user-cancel').addEventListener('click', function () { dlg.close(); });
                dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); }); // click on backdrop
                @if ($errors->has('name') || $errors->has('email') || $errors->has('role')) dlg.showModal(); @endif
            })();
        </script>
    @endpush
</x-layouts.admin>
