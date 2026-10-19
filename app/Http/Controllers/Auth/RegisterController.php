<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\SpccEmail;
use App\Services\OtpService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Throwable;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Step 1 of registration: validate the details, email a 6-digit code and
     * park the pending registration in the session. The account is only
     * created after the code is verified (see OtpController).
     */
    public function store(Request $request, OtpService $otp): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'max:255', new SpccEmail, 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
        ], [
            'name.required' => 'Enter your full name.',
            'email.required' => 'Enter your SPCC email.',
            'email.unique' => 'This email is already registered. Try signing in instead.',
            'password.required' => 'Choose a password.',
        ]);

        try {
            $otp->issue($data['email'], $data['name']);
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput($request->only('name', 'email'))
                ->withErrors(['email' => 'We could not send the verification code. Please try again in a moment.']);
        }

        $request->session()->put('registration', [
            'name' => trim($data['name']),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('otp.show');
    }
}
