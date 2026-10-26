@php
    $tabs = ['all' => 'All Lessons', 'pending' => 'Pending', 'reported' => 'Reported'];
    $field = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20';
@endphp

<x-layouts.admin title="Lesson Management" active="lessons">
    <section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <h2 class="px-6 pt-5 font-semibold text-slate-900">Lesson Management</h2>

        <div class="mt-3 overflow-x-auto border-b border-slate-200 px-6">
            <div class="flex min-w-max gap-6">
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('admin.lessons', ['tab' => $key]) }}"
                       @if ($tab === $key) aria-current="page" @endif
                       class="-mb-px border-b-2 px-1 pb-3 text-sm font-medium transition
                              {{ $tab === $key ? 'border-blue-700 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                        {{ $label }} <span class="ml-1 rounded-full bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500">{{ $counts[$key] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <form method="GET" action="{{ route('admin.lessons') }}" class="flex flex-wrap items-center gap-3 px-6 py-4">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="relative min-w-0 flex-1 sm:max-w-sm">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                <input type="search" name="q" value="{{ $q }}" placeholder="Search lessons..." class="{{ $field }} pl-9">
            </div>
            <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Search</button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[44rem] text-left text-sm">
                <thead class="border-y border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Week</th>
                        <th class="px-6 py-3 font-medium">Title</th>
                        <th class="px-6 py-3 font-medium">Subject</th>
                        <th class="px-6 py-3 font-medium">Uploaded By</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 text-right font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($lessons as $l)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-6 py-3 text-slate-600">{{ $l->week }}</td>
                            <td class="px-6 py-3 font-medium text-slate-900">{{ $l->title }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $l->subject->code }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $l->creator->name ?? '—' }}</td>
                            <td class="px-6 py-3"><x-badge :status="$l->display_status" /></td>
                            <td class="px-6 py-3 text-right">
                                <details class="relative inline-block text-left">
                                    <summary class="grid size-8 cursor-pointer list-none place-items-center rounded-md text-slate-500 hover:bg-slate-100 [&::-webkit-details-marker]:hidden" aria-label="Lesson actions">
                                        <x-icon name="ellipsis-vertical" class="size-5" />
                                    </summary>
                                    <div class="absolute right-0 z-10 mt-1 w-40 rounded-lg bg-white p-1 shadow-lg ring-1 ring-slate-200">
                                        <a href="{{ route('admin.lessons.show', $l) }}" class="block rounded-md px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">Review</a>
                                        @if ($l->isPublished())
                                            <form method="POST" action="{{ route('admin.lessons.unpublish', $l) }}">@csrf
                                                <button type="submit" class="block w-full rounded-md px-3 py-1.5 text-left text-sm text-slate-700 hover:bg-slate-50">Unpublish</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.lessons.publish', $l) }}">@csrf
                                                <button type="submit" class="block w-full rounded-md px-3 py-1.5 text-left text-sm text-slate-700 hover:bg-slate-50">Approve &amp; publish</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.lessons.destroy', $l) }}" onsubmit="return confirm('Remove this lesson and its files?')">@csrf @method('DELETE')
                                            <button type="submit" class="block w-full rounded-md px-3 py-1.5 text-left text-sm text-red-600 hover:bg-red-50">Remove</button>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">No lessons found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($lessons->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">{{ $lessons->links() }}</div>
        @endif
    </section>
</x-layouts.admin>
