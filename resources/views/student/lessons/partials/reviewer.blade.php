{{-- Practice questions. Answers are checked in the browser (sample data only). --}}
<form id="reviewer-form" class="space-y-5">
    @foreach ($questions as $i => $q)
        <fieldset class="rounded-lg border border-slate-200 p-4" data-answer="{{ $q['answer'] }}">
            <legend class="px-1 text-sm font-semibold text-slate-900">{{ $i + 1 }}. {{ $q['q'] }}</legend>
            <div class="mt-2 space-y-1.5">
                @foreach ($q['options'] as $j => $option)
                    <label class="flex cursor-pointer items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-50" data-option="{{ $j }}">
                        <input type="radio" name="q{{ $i }}" value="{{ $j }}" class="size-4 border-slate-300 text-blue-700 focus:ring-blue-600">
                        {{ $option }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endforeach

    <div class="flex items-center gap-4">
        <button type="submit" class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">Check answers</button>
        <p id="reviewer-score" class="text-sm font-medium text-slate-700"></p>
    </div>
</form>

@push('scripts')
    <script>
        (function () {
            var form = document.getElementById('reviewer-form');
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var sets = form.querySelectorAll('fieldset');
                var correct = 0;

                sets.forEach(function (set) {
                    var answer = set.dataset.answer;
                    var picked = set.querySelector('input:checked');
                    set.querySelectorAll('label').forEach(function (l) {
                        l.classList.remove('bg-green-50', 'text-green-700', 'bg-red-50', 'text-red-700');
                    });
                    set.querySelector('[data-option="' + answer + '"]').classList.add('bg-green-50', 'text-green-700');
                    if (picked && picked.value === answer) {
                        correct++;
                    } else if (picked) {
                        picked.closest('label').classList.add('bg-red-50', 'text-red-700');
                    }
                });

                document.getElementById('reviewer-score').textContent = 'Score: ' + correct + ' / ' + sets.length;
            });
        })();
    </script>
@endpush
