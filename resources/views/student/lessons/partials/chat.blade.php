{{--
    AI chat. Answers come only from the uploaded lecture files and always list their sources.
    POST route: student.lessons.chat  (LessonChatController). Everything the AI says is inserted with
    textContent, never as HTML.
--}}
@php
    $readyFiles = $materials->where('processing_status', 'ready')->count();
@endphp

<div class="flex h-[30rem] flex-col rounded-xl ring-1 ring-slate-200">
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b border-slate-200 px-4 py-2.5 text-xs text-slate-600">
        <span class="font-medium text-slate-700">Search in:</span>
        <label class="inline-flex items-center gap-1.5"><input type="radio" name="chat-scope" value="lesson" checked class="text-blue-700 focus:ring-blue-600"> This lesson</label>
        <label class="inline-flex items-center gap-1.5"><input type="radio" name="chat-scope" value="subject" class="text-blue-700 focus:ring-blue-600"> Whole subject ({{ $subject->code }})</label>
    </div>

    <div id="chat-log" class="flex-1 space-y-3 overflow-y-auto bg-slate-50 p-4" aria-live="polite">
        <div class="flex gap-2">
            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-blue-700 text-white"><x-icon name="sparkles" class="size-4" /></span>
            <p class="max-w-[85%] rounded-2xl rounded-tl-sm bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm ring-1 ring-slate-200">
                Hi! Ask me anything about <strong>{{ $lesson['title'] }}</strong>. I answer only from your teacher's uploaded materials and show where each answer comes from.
                @if ($readyFiles === 0)
                    <span class="mt-2 block rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 ring-1 ring-amber-200">The materials for this lesson are still being prepared for the AI, so answers may not be available yet.</span>
                @endif
            </p>
        </div>
    </div>

    <form id="chat-form" class="flex items-center gap-2 border-t border-slate-200 p-3">
        <input id="chat-input" type="text" autocomplete="off" maxlength="500" placeholder="Ask a question about this lesson..."
               class="flex-1 rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20">
        <button id="chat-send" type="submit" class="grid size-10 place-items-center rounded-lg bg-blue-700 text-white transition hover:bg-blue-800 disabled:opacity-60" aria-label="Send">
            <x-icon name="send" class="size-5" />
        </button>
    </form>
</div>

@push('scripts')
    <script>
        (function () {
            var form = document.getElementById('chat-form');
            var input = document.getElementById('chat-input');
            var send = document.getElementById('chat-send');
            var log = document.getElementById('chat-log');
            var url = @json(route('student.lessons.chat', [$subject->code, $lesson->week]));
            var token = @json(csrf_token());

            function scroll() { log.scrollTop = log.scrollHeight; }

            function row(mine) {
                var wrap = document.createElement('div');
                wrap.className = 'flex ' + (mine ? 'justify-end' : 'gap-2');
                log.appendChild(wrap);
                return wrap;
            }

            function bubble(text, mine, extra) {
                var wrap = row(mine);
                var box = document.createElement('div');
                box.className = 'max-w-[85%]';
                var p = document.createElement('p');
                p.className = mine
                    ? 'rounded-2xl rounded-tr-sm bg-blue-700 px-4 py-2.5 text-sm text-white'
                    : 'whitespace-pre-line rounded-2xl rounded-tl-sm bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm ring-1 ring-slate-200' + (extra || '');
                p.textContent = text; // never innerHTML: user and AI text are untrusted
                box.appendChild(p);
                wrap.appendChild(box);
                scroll();
                return box;
            }

            function sources(box, list) {
                if (!list || !list.length) return;
                var wrap = document.createElement('div');
                wrap.className = 'mt-2 space-y-1';
                var title = document.createElement('p');
                title.className = 'text-xs font-semibold text-slate-500';
                title.textContent = 'Sources';
                wrap.appendChild(title);
                list.forEach(function (s) {
                    var a = document.createElement('a');
                    a.href = s.url;
                    a.className = 'block rounded-lg bg-white px-3 py-2 text-xs text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50';
                    a.title = s.snippet || '';
                    var b = document.createElement('strong');
                    b.className = 'text-blue-700';
                    b.textContent = '[' + s.index + '] ';
                    a.appendChild(b);
                    a.appendChild(document.createTextNode(s.file_name + ' \u00b7 ' + s.locator + ' \u00b7 Week ' + s.week));
                    wrap.appendChild(a);
                });
                box.appendChild(wrap);
                scroll();
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var text = input.value.trim();
                if (text.length < 2) return;
                bubble(text, true);
                input.value = '';
                send.disabled = input.disabled = true;
                var thinking = bubble('Looking through the lecture materials...', false, ' italic text-slate-500');
                var scope = document.querySelector('input[name="chat-scope"]:checked').value;

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ question: text, scope: scope })
                })
                    .then(function (r) {
                        return r.json().catch(function () { return {}; }).then(function (body) { return { ok: r.ok, status: r.status, body: body }; });
                    })
                    .then(function (res) {
                        thinking.parentNode.remove();
                        if (!res.ok) {
                            var msg = res.status === 429 ? 'You are asking too fast. Please wait a moment.'
                                    : (res.body.message || 'Something went wrong. Please try again.');
                            bubble(msg, false, ' text-red-700');
                            return;
                        }
                        var box = bubble(res.body.answer, false, res.body.found ? '' : ' text-slate-500');
                        sources(box, res.body.sources);
                    })
                    .catch(function () {
                        thinking.parentNode.remove();
                        bubble('Could not reach the server. Check your connection and try again.', false, ' text-red-700');
                    })
                    .finally(function () {
                        send.disabled = input.disabled = false;
                        input.focus();
                    });
            });
        })();
    </script>
@endpush
