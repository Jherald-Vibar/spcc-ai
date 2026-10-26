@php
    // line chart geometry (inline SVG, no chart library needed)
    $w = 640; $h = 220; $left = 44; $right = 12; $top = 12; $bottom = 28;
    $max = max(4, (int) (ceil(max($usage) / 4) * 4));
    $n = count($usage);
    $pts = [];
    foreach ($usage as $i => $v) {
        $x = $left + ($w - $left - $right) * $i / ($n - 1);
        $y = $top + ($h - $top - $bottom) * (1 - $v / $max);
        $pts[] = round($x, 1).','.round($y, 1);
    }
    $line = implode(' ', $pts);
    $area = $left.','.($h - $bottom).' '.$line.' '.($w - $right).','.($h - $bottom);
    $start = now()->subDays($n - 1);
@endphp

<x-layouts.admin title="Dashboard" active="dashboard">
    <h2 class="text-lg font-semibold text-slate-900">Dashboard Overview</h2>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Total Users', number_format($stats['users']), 'users', 'bg-blue-100 text-blue-700', $stats['growth'] ? '↑ '.$stats['growth'].'% this month' : null],
            ['Students', number_format($stats['students']), 'academic-cap', 'bg-sky-100 text-sky-700', null],
            ['Teachers', number_format($stats['teachers']), 'user', 'bg-orange-100 text-orange-700', null],
            ['Active Lessons', number_format($stats['lessons']), 'book-open', 'bg-green-100 text-green-700', null],
        ] as [$label, $value, $icon, $tone, $note])
            <div class="flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <span class="grid size-11 shrink-0 place-items-center rounded-lg {{ $tone }}">
                    <x-icon :name="$icon" class="size-6" />
                </span>
                <div>
                    <p class="text-sm text-slate-500">{{ $label }}</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $value }}</p>
                    @if ($note) <p class="text-xs font-medium text-green-600">{{ $note }}</p> @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.6fr_1fr]">
        {{-- System usage --}}
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h3 class="font-semibold text-slate-900">System Usage <span class="font-normal text-slate-400">(Last 30 Days)</span></h3>
            <p class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                <span class="inline-block size-2.5 rounded-full bg-blue-600"></span> People who signed in each day
            </p>

            <svg viewBox="0 0 {{ $w }} {{ $h }}" class="mt-4 w-full" role="img" aria-label="Line chart of daily active users over the last 30 days">
                @for ($g = 0; $g <= 4; $g++)
                    @php($gy = $top + ($h - $top - $bottom) * $g / 4)
                    <line x1="{{ $left }}" x2="{{ $w - $right }}" y1="{{ $gy }}" y2="{{ $gy }}" stroke="#e2e8f0" stroke-width="1" />
                    <text x="{{ $left - 8 }}" y="{{ $gy + 4 }}" text-anchor="end" font-size="11" fill="#64748b">{{ number_format($max * (4 - $g) / 4) }}</text>
                @endfor

                <polygon points="{{ $area }}" fill="#2563eb" fill-opacity=".10" />
                <polyline points="{{ $line }}" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />

                @foreach ($usage as $i => $v)
                    @php([$px, $py] = explode(',', $pts[$i]))
                    <circle cx="{{ $px }}" cy="{{ $py }}" r="{{ $i === $n - 1 ? 4 : 2.5 }}" fill="#2563eb"><title>{{ $start->copy()->addDays($i)->format('M j') }}: {{ number_format($v) }} active users</title></circle>
                @endforeach

                @foreach ([0, 7, 14, 21, 29] as $i)
                    @php($lx = $left + ($w - $left - $right) * $i / ($n - 1))
                    <text x="{{ $lx }}" y="{{ $h - 8 }}" text-anchor="middle" font-size="11" fill="#64748b">{{ $start->copy()->addDays($i)->format('M j') }}</text>
                @endforeach
            </svg>
        </section>

        {{-- Recent activities --}}
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h3 class="font-semibold text-slate-900">Recent Activities</h3>
            @if ($activities->isEmpty())
                <p class="mt-4 text-sm text-slate-500">Nothing yet. Activity appears here as users and lessons change.</p>
            @endif
            <ul class="mt-4 space-y-4">
                @foreach ($activities as $a)
                    <li class="flex items-start gap-3">
                        <span class="grid size-9 shrink-0 place-items-center rounded-lg {{ $a['tone'] }}">
                            <x-icon :name="$a['icon']" class="size-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-900">{{ $a['title'] }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $a['sub'] }}</p>
                        </div>
                        <p class="shrink-0 text-xs text-slate-400">{{ $a['time'] }}</p>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
</x-layouts.admin>
