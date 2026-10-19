{{-- Forum UI only; posting needs a route + model. --}}
<form class="mb-5" onsubmit="return false">
    <label for="forum-post" class="sr-only">Start a discussion</label>
    <textarea id="forum-post" rows="3" placeholder="Start a discussion or ask your classmates..."
              class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20"></textarea>
    <button type="submit" class="mt-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800">Post</button>
</form>

<ul class="space-y-3">
    @foreach ($posts as $post)
        <li class="rounded-lg border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <span class="grid size-8 place-items-center rounded-full bg-violet-100 text-xs font-semibold text-violet-700">{{ mb_strtoupper(mb_substr($post['author'], 0, 1)) }}</span>
                <p class="text-sm font-semibold text-slate-900">{{ $post['author'] }}</p>
                <p class="text-xs text-slate-400">{{ $post['time'] }}</p>
            </div>
            <p class="mt-2 text-sm text-slate-700">{{ $post['body'] }}</p>
            <p class="mt-2 text-xs font-medium text-blue-700">{{ $post['replies'] }} {{ \Illuminate\Support\Str::plural('reply', $post['replies']) }}</p>
        </li>
    @endforeach
</ul>
