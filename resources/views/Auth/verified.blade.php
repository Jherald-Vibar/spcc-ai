<x-layouts.auth title="Account verified">
    <div class="flex flex-col items-center text-center">
        <div class="flex size-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
            <x-icon name="check" class="size-9" stroke-width="2.5" />
        </div>

        <h1 class="mt-5 text-xl font-bold text-slate-900">Account Verified!</h1>
        <p class="mt-1.5 text-sm text-slate-500">You can now sign in using your SPCC account.</p>

        <a href="{{ route('login') }}"
           class="mt-6 block w-full rounded-lg bg-blue-700 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2">
            Go to Login
        </a>
    </div>
</x-layouts.auth>
