<x-layouts.auth title="Sign in">
    {{-- Step 1 asks for the email only; "Next" reveals the password step (kept open when there are errors) --}}
    @php($showPassword = $errors->any() || old('email') || $prefill)

    <h1 class="text-center text-lg font-semibold text-slate-900">
        {{ $prefill ? 'Welcome Back!' : 'Sign in with your SPCC account' }}
    </h1>
    <p class="mt-1 text-center text-sm text-slate-500">
        {{ $prefill ? 'Your account is verified. Sign in with your SPCC account.' : 'Use your school email to continue.' }}
    </p>

    <form method="POST" action="{{ route('login.store') }}" id="login-form" class="mt-6 space-y-4" novalidate>
        @csrf

        <x-auth.alerts />

        <div>
            <x-auth.input name="email" type="email" label="SPCC email" icon="envelope" :value="$prefill"
                          placeholder="Surname_name@spcc.edu.ph" autocomplete="username" autofocus />
            <p id="email-error" class="mt-1.5 hidden text-sm text-red-600"></p>
        </div>

        <div id="password-step" class="{{ $showPassword ? '' : 'hidden' }} space-y-4">
            <x-auth.input name="password" type="password" label="Password" icon="lock-closed"
                          placeholder="Enter your password" autocomplete="current-password" />

            <div class="flex items-center justify-between text-sm">
                <label class="inline-flex cursor-pointer items-center gap-2 text-slate-600">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                           class="size-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                    Remember me
                </label>
                <a href="#" class="font-medium text-blue-700 hover:text-blue-800 hover:underline">Forgot password?</a>
            </div>
        </div>

        <button type="button" id="next-btn"
                class="{{ $showPassword ? 'hidden' : '' }} w-full rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2">
            Next
        </button>

        <button type="submit" id="signin-btn"
                class="{{ $showPassword ? '' : 'hidden' }} w-full rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2">
            Sign In
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        Don't have an account?
        <a href="{{ route('register') }}" class="font-semibold text-blue-700 hover:text-blue-800 hover:underline">Register</a>
    </p>

    @push('scripts')
        <script>
            (function () {
                var form = document.getElementById('login-form');
                var email = document.getElementById('email');
                var password = document.getElementById('password');
                var step = document.getElementById('password-step');
                var nextBtn = document.getElementById('next-btn');
                var signinBtn = document.getElementById('signin-btn');
                var error = document.getElementById('email-error');
                var spcc = /^[a-z0-9._-]+@spcc\.edu\.ph$/i;

                function goNext() {
                    if (!spcc.test(email.value.trim())) {
                        error.textContent = 'Only SPCC accounts are allowed. Use Surname_name@spcc.edu.ph.';
                        error.classList.remove('hidden');
                        email.focus();
                        return;
                    }
                    error.classList.add('hidden');
                    step.classList.remove('hidden');
                    nextBtn.classList.add('hidden');
                    signinBtn.classList.remove('hidden');
                    password.focus();
                }

                nextBtn.addEventListener('click', goNext);

                // Enter in the email box moves to the password step instead of submitting
                form.addEventListener('submit', function (e) {
                    if (step.classList.contains('hidden')) {
                        e.preventDefault();
                        goNext();
                    }
                });

                if (!step.classList.contains('hidden') && email.value) {
                    password.focus();
                }
            })();
        </script>
    @endpush
</x-layouts.auth>
