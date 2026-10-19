{{-- AI video player. Shows the real <video> once $lesson['video_url'] is set, otherwise a placeholder. --}}
@if (! empty($lesson['video_url']))
    <video controls class="aspect-video w-full rounded-xl bg-slate-900" src="{{ $lesson['video_url'] }}"></video>
@else
    <div class="relative aspect-video overflow-hidden rounded-xl bg-gradient-to-br from-slate-900 via-blue-950 to-blue-900 text-white">
        <div class="absolute inset-0 grid place-items-center">
            <div class="text-center">
                <span class="mx-auto grid size-16 place-items-center rounded-2xl bg-white/10 ring-1 ring-white/20">
                    <x-icon name="lock-closed" class="size-8 text-blue-200" />
                </span>
                <p class="mt-3 text-xl font-bold">{{ $lesson['title'] }}</p>
                <p class="mt-1 text-sm text-blue-200">AI video not available yet</p>
            </div>
        </div>
        <div class="absolute inset-x-0 bottom-0 flex items-center gap-3 bg-black/40 px-4 py-2 text-xs">
            <x-icon name="play" class="size-4" />
            <span>0:00 / {{ $lesson['duration'] }}</span>
            <div class="h-1 flex-1 rounded-full bg-white/20"></div>
        </div>
    </div>
@endif
