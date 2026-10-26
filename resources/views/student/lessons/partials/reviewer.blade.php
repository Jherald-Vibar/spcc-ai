{{--
    Reviewer: 5 multiple-choice questions the AI wrote from this lesson's materials.
    The page only receives the questions and options; answers are graded by POST student.quiz.submit.
--}}
@if ($questions->isEmpty())
    <div class="rounded-lg border border-dashed border-slate-300 px-6 py-10 text-center text-sm text-slate-500">
        @if ($quizStatus === 'generating')
            The AI is writing practice questions from this lesson's materials. Check back in a minute.
        @elseif ($quizStatus === 'failed')
            The practice questions could not be prepared right now. Your teacher can re-send the materials to try again.
        @else
            Practice questions will appear here once this lesson's materials have been processed by the AI.
        @endif
    </div>
@else
    <p class="mb-4 text-xs text-slate-500">Written by the AI from your teacher's uploaded materials.
        @if ($lastAttempt)
            Last score: <strong class="text-slate-700">{{ $lastAttempt->score }} / {{ $lastAttempt->total }}</strong>.
        @endif
    </p>

    <form id="reviewer-form" class="space-y-5">
        @foreach ($questions as $i => $q)
            <fieldset class="rounded-lg border border-slate-200 p-4" data-qid="{{ $q->id }}">
                <legend class="px-1 text-sm font-semibold text-slate-900">{{ $i + 1 }}. {{ $q->question }}</legend>
                <div class="mt-2 space-y-1.5">
                    @foreach ($q->options as $j => $option)
                        <label class="flex cursor-pointer items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-50" data-option="{{ $j }}">
                            <input type="radio" name="q{{ $q->id }}" value="{{ $j }}" class="size-4 border-slate-300 text-blue-700 focus:ring-blue-600">
                            {{ $option }}
                        </label>
                    @endforeach
                </div>
                <p data-explanation class="mt-2 hidden rounded-md bg-slate-50 px-3 py-2 text-xs text-slate-600"></p>
            </fieldset>
        @endforeach

        <div class="flex flex-wrap items-center gap-4">
            <button id="reviewer-submit" type="submit" class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:opacity-60">Check answers</button>
            <p id="reviewer-score" class="text-sm font-medium text-slate-700" aria-live="polite"></p>
        </div>
    </form>

    @push('scripts')
        <script>
            (function () {
                var form = document.getElementById('reviewer-form');
                var button = document.getElementById('reviewer-submit');
                var scoreEl = document.getElementById('reviewer-score');
                var url = @json(route('student.quiz.submit', [$subject->code, $lesson->week]));
                var token = @json(csrf_token());
                var good = ['bg-green-50', 'text-green-700'];
                var bad = ['bg-red-50', 'text-red-700'];

                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var answers = {};
                    var sets = form.querySelectorAll('fieldset');
                    var unanswered = 0;

                    sets.forEach(function (set) {
                        var picked = set.querySelector('input:checked');
                        if (picked) { answers[set.dataset.qid] = parseInt(picked.value, 10); } else { unanswered++; }
                    });

                    if (unanswered && !confirm(unanswered + ' question(s) are not answered. Check anyway?')) return;

                    button.disabled = true;
                    scoreEl.textContent = 'Checking...';

                    fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ answers: answers })
                    })
                        .then(function (r) { return r.json().then(function (body) { return { ok: r.ok, body: body }; }); })
                        .then(function (res) {
                            if (!res.ok) { scoreEl.textContent = res.body.message || 'Could not check your answers. Please try again.'; return; }

                            res.body.results.forEach(function (r) {
                                var set = form.querySelector('[data-qid="' + r.id + '"]');
                                set.querySelectorAll('label').forEach(function (l) {
                                    good.concat(bad).forEach(function (c) { l.classList.remove(c); });
                                });
                                var right = set.querySelector('[data-option="' + r.answer + '"]');
                                good.forEach(function (c) { right.classList.add(c); });
                                if (r.picked !== null && !r.correct) {
                                    var wrong = set.querySelector('[data-option="' + r.picked + '"]');
                                    if (wrong) bad.forEach(function (c) { wrong.classList.add(c); });
                                }
                                var note = set.querySelector('[data-explanation]');
                                if (r.explanation) { note.textContent = r.explanation; note.classList.remove('hidden'); }
                            });

                            scoreEl.textContent = 'Score: ' + res.body.score + ' / ' + res.body.total;
                        })
                        .catch(function () { scoreEl.textContent = 'Could not reach the server. Please try again.'; })
                        .finally(function () { button.disabled = false; });
                });
            })();
        </script>
    @endpush
@endif
