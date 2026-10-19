{{--
    AI chat UI. The send handler below is a stub: wire fetch() to your AI endpoint
    (e.g. POST /student/lessons/{subject}/week/{week}/chat) where marked.
--}}
<div class="flex h-[28rem] flex-col rounded-xl ring-1 ring-slate-200">
    <div id="chat-log" class="flex-1 space-y-3 overflow-y-auto bg-slate-50 p-4">
        <div class="flex gap-2">
            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-blue-700 text-white"><x-icon name="sparkles" class="size-4" /></span>
            <p class="max-w-[85%] rounded-2xl rounded-tl-sm bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm ring-1 ring-slate-200">
                Hi! Ask me anything about <strong>{{ $lesson['title'] }}</strong>.
            </p>
        </div>
    </div>

    <form id="chat-form" class="flex items-center gap-2 border-t border-slate-200 p-3">
        <input id="chat-input" type="text" autocomplete="off" placeholder="Ask a question about this lesson..."
               class="flex-1 rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20">
        <button type="submit" class="grid size-10 place-items-center rounded-lg bg-blue-700 text-white transition hover:bg-blue-800" aria-label="Send">
            <x-icon name="send" class="size-5" />
        </button>
    </form>
</div>

@push('scripts')
    <script>
        (function () {
            var form = document.getElementById('chat-form');
            var input = document.getElementById('chat-input');
            var log = document.getElementById('chat-log');

            function bubble(text, mine) {
                var wrap = document.createElement('div');
                wrap.className = 'flex ' + (mine ? 'justify-end' : 'gap-2');
                var p = document.createElement('p');
                p.className = mine
                    ? 'max-w-[85%] rounded-2xl rounded-tr-sm bg-blue-700 px-4 py-2.5 text-sm text-white'
                    : 'max-w-[85%] rounded-2xl rounded-tl-sm bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm ring-1 ring-slate-200';
                p.textContent = text; // textContent: never inject user/AI text as HTML
                wrap.appendChild(p);
                log.appendChild(wrap);
                log.scrollTop = log.scrollHeight;
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var text = input.value.trim();
                if (!text) return;
                bubble(text, true);
                input.value = '';

                // TODO: replace with a real call to your AI endpoint, then bubble(reply, false)
                bubble('AI chat is not connected yet.', false);
            });
        })();
    </script>
@endpush
