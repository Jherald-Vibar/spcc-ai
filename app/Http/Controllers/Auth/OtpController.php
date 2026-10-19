<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class OtpController extends Controller
{
    /** Step 2: the 6-digit code screen. */
    public function create(Request $request, OtpService $otp): View|RedirectResponse
    {
        $registration = $request->session()->get('registration');

        if (! $registration) {
            return redirect()->route('register');
        }

        return view('auth.otp', [
            'email' => $registration['email'],
            'cooldown' => $otp->cooldownRemaining($registration['email']),
        ]);
    }

    /** Step 3: check the code and create the verified account. */
    public function store(Request $request, OtpService $otp): RedirectResponse
    {
        $registration = $request->session()->get('registration');

        if (! $registration) {
            return redirect()->route('register');
        }

        $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Enter the 6-digit code.',
            'code.digits' => 'Enter the 6-digit code.',
        ]);

        $result = $otp->verify($registration['email'], (string) $request->input('code'));

        if ($result !== OtpService::OK) {
            $message = match ($result) {
                OtpService::EXPIRED => 'This code has expired. Request a new one.',
                OtpService::LOCKED => 'Too many wrong attempts. Request a new code.',
                default => 'That code is not correct. Please try again.',
            };

            return back()->withErrors(['code' => $message]);
        }

        if (User::where('email', $registration['email'])->exists()) {
            $otp->forget($registration['email']);
            $request->session()->forget('registration');

            return redirect()->route('login')
                ->withErrors(['email' => 'This email is already registered. Please sign in.']);
        }

        $user = new User([
            'name' => $registration['name'],
            'email' => $registration['email'],
            'password' => $registration['password'], // already hashed in RegisterController
        ]);

        // self-registration always creates a student; teacher/admin accounts are assigned by an administrator
        $user->forceFill([
            'role' => User::ROLE_STUDENT,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ])->save();

        $otp->forget($registration['email']);
        $request->session()->forget('registration');
        $request->session()->put('verified_email', $user->email);

        return redirect()->route('register.complete');
    }

    /** Resend the code (30 second cooldown enforced server-side). */
    public function resend(Request $request, OtpService $otp): RedirectResponse
    {
        $registration = $request->session()->get('registration');

        if (! $registration) {
            return redirect()->route('register');
        }

        if ($otp->cooldownRemaining($registration['email']) > 0) {
            return back()->withErrors(['code' => 'Please wait a few seconds before requesting another code.']);
        }

        try {
            $otp->issue($registration['email'], $registration['name']);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['code' => 'We could not send the code. Please try again in a moment.']);
        }

        return back()->with('status', 'A new code was sent to your email.');
    }

    /** Step 4: "Account Verified!" */
    public function complete(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('verified_email')) {
            return redirect()->route('login');
        }

        return view('auth.verified');
    }
}
