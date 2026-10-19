@props(['status'])
@php
    $map = [
        'completed'   => ['Completed',   'bg-green-100 text-green-700 ring-green-200'],
        'in_progress' => ['In Progress', 'bg-amber-100 text-amber-700 ring-amber-200'],
        'upcoming'    => ['Upcoming',    'bg-slate-100 text-slate-600 ring-slate-200'],
    ];
    [$label, $classes] = $map[$status] ?? $map['upcoming'];
@endphp
<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset', $classes]) }}>{{ $label }}</span>
