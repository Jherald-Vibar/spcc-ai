{{-- Generic status chip: <x-badge status="published" /> --}}
@props(['status'])
@php
    $map = [
        'published' => ['Published', 'bg-green-100 text-green-700 ring-green-200'],
        'draft'     => ['Draft',     'bg-orange-100 text-orange-700 ring-orange-200'],
        'pending'   => ['Pending',   'bg-blue-100 text-blue-700 ring-blue-200'],
        'reported'  => ['Reported',  'bg-red-100 text-red-700 ring-red-200'],
        'active'    => ['Active',    'bg-green-100 text-green-700 ring-green-200'],
        'inactive'  => ['Inactive',  'bg-slate-100 text-slate-600 ring-slate-200'],
    ];
    [$label, $classes] = $map[$status] ?? [ucfirst((string) $status), 'bg-slate-100 text-slate-600 ring-slate-200'];
@endphp
<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset', $classes]) }}>{{ $label }}</span>
