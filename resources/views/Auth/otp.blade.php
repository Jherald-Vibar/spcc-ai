<x-layouts.auth title="Verify your email">
    <h1 class="text-center text-lg font-semibold text-slate-900">Enter the 6-digit code</h1>
    <p class="mt-1 text-center text-sm text-slate-500">
        We sent the code to<br>
        <span class="break-all font-semibold text-slate-800">{{ $email }}</span>
    </p>

    <form method="POST" action="{{ route('otp.verify') }}" id="otp-form" class="mt-6 space-y-5" novalidate>
        @csrf

        <x-auth.alerts />

        <input type="hidden" name="code" id="otp-code">

        <div id="otp-boxes" class="flex justify-center gap-2 sm:gap-3" role="group" aria-label="6-digit verification code">
            @for ($i = 0; $i < 6; $i++)
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1"
                       autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}" aria-label="Digit {{ $i + 1 }}"
                       class="size-11 rounded-lg border border-slate-300 bg-white text-center text-xl font-semibold text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 sm:size-12">
            @endfor
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2">
            Verify
        </button>
    </form>

    <form method="POST" action="{{ route('otp.resend') }}" class="mt-5 text-center">
        @csrf
        <button type="submit" id="resend-btn" data-cooldown="{{ $cooldown }}"
                class="text-sm font-medium text-blue-700 hover:text-blue-800 hover:underline disabled:cursor-not-allowed disabled:text-slate-400 disabled:no-underline">
            Resend code <span id="resend-timer"></span>
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-slate-500">
        Wrong email?
        <a href="{{ route('register') }}" class="font-medium text-blue-700 hover:text-blue-800 hover:underline">Start over</a>
    </p>

    @push('scripts')
        <script>
            (function () {
                // --- six digit boxes -------------------------------------------------
                var boxes = Array.prototype.slice.call(document.querySelectorAll('#otp-boxes input'));
                var hidden = document.getElementById('otp-code');

                function sync() {
                    hidden.value = boxes.map(function (b) { return b.value; }).join('');
                }

                boxes.forEach(function (box, i) {
                    box.addEventListener('input', function () {
                        box.value = box.value.replace(/\D/g, '').slice(-1);
                        sync();
                        if (box.value && i < boxes.length - 1) boxes[i + 1].focus();
                    });

                    box.addEventListener('keydown', function (e) {
                        if (e.key === 'Backspace' && !box.value && i > 0) {
                            boxes[i - 1].focus();
                            boxes[i - 1].value = '';
                            sync();
                        } else if (e.key === 'ArrowLeft' && i > 0) {
                            boxes[i - 1].focus();
                        } else if (e.key === 'ArrowRight' && i < boxes.length - 1) {
                            boxes[i + 1].focus();
                        }
                    });

                    box.addEventListener('focus', function () { box.select(); });

                    box.addEventListener('paste', function (e) {
                        e.preventDefault();
                        var digits = ((e.clipboardData || window.clipboardData).getData('text') || '').replace(/\D/g, '').slice(0, boxes.length);
                        digits.split('').forEach(function (d, k) { boxes[k].value = d; });
                        sync();
                        boxes[Math.min(digits.length, boxes.length - 1)].focus();
                    });
                });

                document.getElementById('otp-form').addEventListener('submit', sync);
                boxes[0].focus();

                // --- resend countdown ------------------------------------------------
                var btn = document.getElementById('resend-btn');
                var label = document.getElementById('resend-timer');
                var left = parseInt(btn.dataset.cooldown, 10) || 0;

                function render() {
                    if (left > 0) {
                        var m = String(Math.floor(left / 60)).padStart(2, '0');
                        var s = String(left % 60).padStart(2, '0');
                        label.textContent = '(' + m + ':' + s + ')';
                        btn.disabled = true;
                    } else {
                        label.textContent = '';
                        btn.disabled = false;
                    }
                }

                render();
                if (left > 0) {
                    var timer = setInterval(function () {
                        left--;
                        render();
                        if (left <= 0) clearInterval(timer);
                    }, 1000);
                }
            })();
        </script>
    @endpush
</x-layouts.auth>
