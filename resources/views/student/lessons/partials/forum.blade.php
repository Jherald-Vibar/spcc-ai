{{-- Forum: discussions and replies stored in forum_posts. Everything is escaped by Blade. --}}
@php
    $me = auth()->id();
    $initial = fn ($name) => mb_strtoupper(mb_substr($name, 0, 1));
@endphp

<form method="POST" action="{{ route('student.forum.store', [$subject->code, $lesson->week]) }}" class="mb-5">
    @csrf
    <label for="forum-post" class="sr-only">Start a discussion</label>
    <textarea id="forum-post" name="body" rows="3" maxlength="1000" required
              placeholder="Start a discussion or ask your classmates..."
              class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2
                     {{ $errors->has('body') && ! old('parent_id') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-blue-600 focus:ring-blue-600/20' }}">{{ old('parent_id') ? '' : old('body') }}</textarea>
    @if ($errors->has('body') && ! old('parent_id'))
        <p class="mt-1 text-xs text-red-600">{{ $errors->first('body') }}</p>
    @endif
    <button type="submit" class="mt-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800">Post</button>
</form>

@forelse ($posts as $post)
    <article class="mb-3 rounded-lg border border-slate-200 p-4">
        <div class="flex flex-wrap items-center gap-3">
            <span class="grid size-8 place-items-center rounded-full bg-violet-100 text-xs font-semibold text-violet-700">{{ $initial($post->user->name) }}</span>
            <p class="text-sm font-semibold text-slate-900">{{ $post->user->name }}</p>
            @if ($post->user->role === 'teacher')
                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-blue-200">Teacher</span>
            @endif
            <p class="text-xs text-slate-400">{{ $post->created_at->diffForHumans() }}</p>

            @if ($post->user_id === $me)
                <form method="POST" action="{{ route('student.forum.destroy', $post) }}" class="ml-auto"
                      onsubmit="return confirm('Delete this post and its replies?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs font-medium text-slate-400 hover:text-red-600">Delete</button>
                </form>
            @endif
        </div>

        <p class="mt-2 whitespace-pre-line break-words text-sm text-slate-700">{{ $post->body }}</p>

        @if ($post->replies->isNotEmpty())
            <ul class="mt-3 space-y-2 border-l-2 border-slate-100 pl-4">
                @foreach ($post->replies as $reply)
                    <li>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-slate-900">{{ $reply->user->name }}</p>
                            @if ($reply->user->role === 'teacher')
                                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-blue-200">Teacher</span>
                            @endif
                            <p class="text-xs text-slate-400">{{ $reply->created_at->diffForHumans() }}</p>
                            @if ($reply->user_id === $me)
                                <form method="POST" action="{{ route('student.forum.destroy', $reply) }}" class="ml-auto"
                                      onsubmit="return confirm('Delete this reply?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-slate-400 hover:text-red-600">Delete</button>
                                </form>
                            @endif
                        </div>
                        <p class="whitespace-pre-line break-words text-sm text-slate-700">{{ $reply->body }}</p>
                    </li>
                @endforeach
            </ul>
        @endif

        <details class="mt-3" @if ((int) old('parent_id') === $post->id) open @endif>
            <summary class="cursor-pointer list-none text-xs font-medium text-blue-700 hover:underline [&::-webkit-details-marker]:hidden">
                {{ $post->replies->count() }} {{ \Illuminate\Support\Str::plural('reply', $post->replies->count()) }} &middot; Reply
            </summary>
            <form method="POST" action="{{ route('student.forum.store', [$subject->code, $lesson->week]) }}" class="mt-2">
                @csrf
                <input type="hidden" name="parent_id" value="{{ $post->id }}">
                <label for="reply-{{ $post->id }}" class="sr-only">Write a reply</label>
                <textarea id="reply-{{ $post->id }}" name="body" rows="2" maxlength="1000" required placeholder="Write a reply..."
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20">{{ (int) old('parent_id') === $post->id ? old('body') : '' }}</textarea>
                @if ($errors->has('body') && (int) old('parent_id') === $post->id)
                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('body') }}</p>
                @endif
                <button type="submit" class="mt-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Reply</button>
            </form>
        </details>
    </article>
@empty
    <p class="rounded-lg border border-dashed border-slate-300 px-6 py-10 text-center text-sm text-slate-500">No discussions yet. Be the first to ask a question.</p>
@endforelse
