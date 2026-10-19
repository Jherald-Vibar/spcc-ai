{{-- Success message (session 'status') and validation errors, shown at the top of a form. --}}
@if (session('status'))
    <div role="status" class="flex items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-sm text-emerald-800">
        <x-icon name="check" class="mt-0.5 size-4 shrink-0" />
        <span>{{ session('status') }}</span>
    </div>
@endif

@if ($errors->any())
    <div role="alert" class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700">
        <x-icon name="exclamation-circle" class="mt-0.5 size-4 shrink-0" />
        <div class="space-y-1">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    </div>
@endif
