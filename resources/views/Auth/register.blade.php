<x-layouts.auth title="Register">
    <h1 class="text-center text-lg font-semibold text-slate-900">Register with your SPCC Email</h1>
    <p class="mt-1 text-center text-sm text-slate-500">We will send a 6-digit code to verify your email.</p>

    <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-4" novalidate>
        @csrf

        <x-auth.alerts />

        <x-auth.input name="name" label="Full name" icon="user"
                      placeholder="Juan Dela Cruz" autocomplete="name" required />

        <x-auth.input name="email" type="email" label="SPCC email" icon="envelope"
                      placeholder="Surname_name@spcc.edu.ph" autocomplete="username" required />

        <div class="flex items-start gap-3 rounded-lg border border-blue-100 bg-blue-50 px-3.5 py-3">
            <x-icon name="academic-cap" class="mt-0.5 size-6 shrink-0 text-blue-700" />
            <div class="text-sm">
                <p class="font-semibold text-blue-900">Only SPCC accounts are allowed</p>
                <p class="mt-0.5 text-blue-800/80">Format: Surname_name@spcc.edu.ph</p>
            </div>
        </div>

        <div>
            <x-auth.input name="password" type="password" label="Password" icon="lock-closed"
                          placeholder="Create a password" autocomplete="new-password" required />
            <p class="mt-1.5 text-xs text-slate-500">At least 8 characters, with letters and numbers.</p>
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2">
            Send OTP
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-blue-700 hover:text-blue-800 hover:underline">Sign in</a>
    </p>
</x-layouts.auth>
