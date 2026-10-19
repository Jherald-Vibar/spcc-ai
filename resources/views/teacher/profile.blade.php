<x-layouts.teacher title="Profile" active="profile">
    @php($user = auth()->user())
    <div class="max-w-2xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center gap-4">
            <span class="grid size-16 place-items-center rounded-full bg-blue-700 text-xl font-bold text-white">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
            <div>
                <h2 class="text-lg font-bold text-slate-900">{{ $user->name }}</h2>
                <p class="text-sm text-slate-500">Teacher</p>
            </div>
        </div>
        <dl class="mt-6 divide-y divide-slate-100 text-sm">
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Full name</dt><dd class="font-medium text-slate-900">{{ $user->name }}</dd></div>
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">SPCC email</dt><dd class="font-medium text-slate-900">{{ $user->email }}</dd></div>
            <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Role</dt><dd class="font-medium text-slate-900">Teacher</dd></div>
        </dl>
    </div>
</x-layouts.teacher>
