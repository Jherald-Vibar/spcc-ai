<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Placeholders so you can test login end to end.
 * The real student / teacher / admin layouts replace these views next.
 */
class DashboardController extends Controller
{
    /** Sends each signed-in user to the dashboard for their role. */
    public function index(): RedirectResponse
    {
        $route = match (auth()->user()->role) {
            'admin' => 'admin.dashboard',
            'teacher' => 'teacher.dashboard',
            default => 'student.dashboard',
        };

        return redirect()->route($route);
    }

    public function student(): View
    {
        return view('dashboard', ['roleLabel' => 'Student']);
    }

    public function teacher(): View
    {
        return view('dashboard', ['roleLabel' => 'Teacher']);
    }

    public function admin(): View
    {
        return view('dashboard', ['roleLabel' => 'Administrator']);
    }
}
