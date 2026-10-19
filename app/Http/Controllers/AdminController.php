<?php

namespace App\Http\Controllers;

use App\Support\SampleStaffData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminController extends Controller
{
    private const ROLE_TABS = [
        'students'       => 'student',
        'teachers'       => 'teacher',
        'administrators' => 'administrator',
    ];

    public function dashboard(): View
    {
        $usage = SampleStaffData::usage();

        return view('admin.dashboard', [
            'stats'      => SampleStaffData::stats(),
            'usage'      => $usage,
            'activities' => SampleStaffData::activities(),
        ]);
    }

    public function users(Request $request): View
    {
        $tab    = array_key_exists((string) $request->query('role'), self::ROLE_TABS) ? (string) $request->query('role') : 'students';
        $q      = Str::lower(trim((string) $request->query('q', '')));
        $status = in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : null;

        $all = collect(SampleStaffData::users());

        $counts = [];
        foreach (self::ROLE_TABS as $key => $role) {
            $counts[$key] = $all->where('role', $role)->count();
        }

        $users = $all->where('role', self::ROLE_TABS[$tab])
            ->when($status, fn ($c) => $c->where('status', $status))
            ->when($q !== '', fn ($c) => $c->filter(
                fn ($u) => Str::contains(Str::lower($u['name'].' '.$u['email']), $q)
            ))
            ->values();

        return view('admin.users', compact('tab', 'q', 'status', 'counts', 'users'));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'regex:/^[A-Za-z0-9._-]+@spcc\.edu\.ph$/i'],
            'role'  => ['required', 'in:student,teacher,administrator'],
        ], [
            'email.regex' => 'Only SPCC accounts are allowed. Use Surname_name@spcc.edu.ph.',
        ]);

        // TODO: create the user (User::create(...)) here. Nothing is saved yet.

        return redirect()
            ->route('admin.users', ['role' => Str::plural($data['role'])])
            ->with('status', "{$data['name']} ({$data['email']}) added as {$data['role']}. (Demo only: connect the User model in AdminController@storeUser to actually save it.)");
    }

    public function lessons(Request $request): View
    {
        $tab = in_array($request->query('tab'), ['all', 'pending', 'reported'], true) ? $request->query('tab') : 'all';
        $q   = Str::lower(trim((string) $request->query('q', '')));

        $all = collect(SampleStaffData::lessons());

        $counts = [
            'all'      => $all->count(),
            'pending'  => $all->where('status', 'pending')->count(),
            'reported' => $all->where('status', 'reported')->count(),
        ];

        $lessons = $all
            ->when($tab !== 'all', fn ($c) => $c->where('status', $tab))
            ->when($q !== '', fn ($c) => $c->filter(
                fn ($l) => Str::contains(Str::lower($l['title'].' '.$l['subject'].' '.$l['by']), $q)
            ))
            ->values();

        return view('admin.lessons', compact('tab', 'q', 'counts', 'lessons'));
    }

    public function reports(): View
    {
        return view('admin.reports');
    }

    public function settings(): View
    {
        return view('admin.settings');
    }
}
