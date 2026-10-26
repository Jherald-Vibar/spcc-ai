@if (count($materials))
    <ul class="divide-y divide-slate-100 rounded-lg ring-1 ring-slate-200">
        @foreach ($materials as $file)
            <li class="flex items-center gap-4 px-4 py-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-sky-100 text-xs font-bold text-sky-700">{{ $file['type'] }}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-slate-900">{{ $file['name'] }}</p>
                    <p class="text-xs text-slate-500">{{ $file['size'] }}</p>
                </div>
                <a href="{{ route('student.files.download', $file) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <x-icon name="download" class="size-4" /> Download
                </a>
            </li>
        @endforeach
    </ul>
@else
    <p class="rounded-lg border border-dashed border-slate-300 px-6 py-10 text-center text-sm text-slate-500">No materials uploaded for this lesson yet.</p>
@endif
