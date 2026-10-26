<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Lesson;
use App\Models\User;
use App\Rules\SpccEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    /** tab key in the URL => users.role in the database */
    private const ROLE_TABS = [
        'students'       => User::ROLE_STUDENT,
        'teachers'       => User::ROLE_TEACHER,
        'administrators' => User::ROLE_ADMIN,
    ];

    /* -------------------------------------------------------------- dashboard */

    public function dashboard(): View
    {
        $total = User::count();
        $newThisMonth = User::where('created_at', '>=', now()->subDays(30))->count();
        $before = $total - $newThisMonth;

        // distinct people who signed in on each of the last 30 days
        $days = 30;
        $logins = ActivityLog::where('type', 'login')
            ->where('created_at', '>=', today()->subDays($days - 1))
            ->get(['user_id', 'created_at'])
            ->groupBy(fn ($row) => $row->created_at->toDateString())
            ->map(fn ($rows) => $rows->pluck('user_id')->unique()->count());

        $usage = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $usage[] = (int) ($logins[today()->subDays($i)->toDateString()] ?? 0);
        }

        $activities = ActivityLog::where('type', '!=', 'login')
            ->latest()->limit(6)->get()
            ->map(function (ActivityLog $a) {
                [$icon, $tone] = $a->style();

                return [
                    'title' => $a->title,
                    'sub'   => $a->detail,
                    'time'  => $a->created_at->timezone(config('app.timezone'))->format('M j, g:i A'),
                    'tone'  => $tone,
                    'icon'  => $icon,
                ];
            });

        return view('admin.dashboard', [
            'stats' => [
                'users'    => $total,
                'students' => User::where('role', User::ROLE_STUDENT)->count(),
                'teachers' => User::where('role', User::ROLE_TEACHER)->count(),
                'lessons'  => Lesson::published()->count(),
                'growth'   => $before > 0 ? (int) round($newThisMonth / $before * 100) : null,
            ],
            'usage'      => $usage,
            'activities' => $activities,
        ]);
    }

    /* ------------------------------------------------------------------ users */

    public function users(Request $request): View
    {
        $tab    = array_key_exists((string) $request->query('role'), self::ROLE_TABS) ? (string) $request->query('role') : 'students';
        $q      = trim((string) $request->query('q', ''));
        $status = in_array($request->query('status'), [User::STATUS_ACTIVE, User::STATUS_INACTIVE], true) ? $request->query('status') : null;

        $byRole = User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');
        $counts = [];
        foreach (self::ROLE_TABS as $key => $role) {
            $counts[$key] = (int) ($byRole[$role] ?? 0);
        }

        $users = User::where('role', self::ROLE_TABS[$tab])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users', compact('tab', 'q', 'status', 'counts', 'users'));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name'       => ['required', 'string', 'max:120'],
            'email'      => ['required', 'string', 'max:255', new SpccEmail, 'unique:users,email'],
            'role'       => ['required', Rule::in([User::ROLE_STUDENT, User::ROLE_TEACHER, User::ROLE_ADMIN])],
            'year_level' => ['nullable', 'integer', 'between:1,4'],
        ], [
            'email.unique' => 'That email already has an account.',
        ]);

        $temporary = $this->temporaryPassword();

        $user = new User;
        $user->forceFill([
            'name'              => trim($data['name']),
            'email'             => $data['email'],
            'password'          => $temporary,
            'role'              => $data['role'],
            'status'            => User::STATUS_ACTIVE,
            'year_level'        => $data['role'] === User::ROLE_STUDENT ? ($data['year_level'] ?? null) : null,
            'email_verified_at' => now(),
        ])->save();

        ActivityLog::record('user_created', 'New user added', ucfirst($user->role).' account: '.$user->email);

        return redirect()
            ->route('admin.users', ['role' => array_search($user->role, self::ROLE_TABS, true)])
            ->with('status', "{$user->name} added as {$user->role}. Temporary password: {$temporary}  (shown once, give it to the user).");
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        // nobody can change their own role (the form also disables the field, so it is not submitted)
        if ($user->is($request->user())) {
            $request->merge(['role' => $user->role]);
        }

        $data = $request->validate([
            'name'       => ['required', 'string', 'max:120'],
            'email'      => ['required', 'string', 'max:255', new SpccEmail, Rule::unique('users', 'email')->ignore($user->id)],
            'role'       => ['required', Rule::in([User::ROLE_STUDENT, User::ROLE_TEACHER, User::ROLE_ADMIN])],
            'year_level' => ['nullable', 'integer', 'between:1,4'],
        ]);

        $user->forceFill([
            'name'       => trim($data['name']),
            'email'      => $data['email'],
            'role'       => $data['role'],
            'year_level' => $data['role'] === User::ROLE_STUDENT ? ($data['year_level'] ?? null) : null,
        ])->save();

        return redirect()
            ->route('admin.users', ['role' => array_search($user->role, self::ROLE_TABS, true)])
            ->with('status', "{$user->name} updated.");
    }

    public function toggleUser(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['status' => 'You cannot deactivate your own account.']);
        }

        $user->forceFill([
            'status' => $user->isActive() ? User::STATUS_INACTIVE : User::STATUS_ACTIVE,
        ])->save();

        return back()->with('status', "{$user->name} is now {$user->status}.");
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $temporary = $this->temporaryPassword();
        $user->forceFill(['password' => $temporary])->save();

        return back()->with('status', "New temporary password for {$user->name}: {$temporary}  (shown once, give it to the user).");
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['status' => 'You cannot delete your own account.']);
        }

        $name = $user->name;
        $user->delete();   // progress goes with the user; subjects they taught become unassigned

        return back()->with('status', "{$name} was deleted.");
    }

    /* -------------------------------------------------------- placeholder pages */

    public function reports(): View
    {
        return view('admin.reports');
    }

    public function settings(): View
    {
        return view('admin.settings');
    }

    /** Letters and digits only, so it always passes the "letters + numbers" password rule. */
    private function temporaryPassword(): string
    {
        return Str::random(8).chr(random_int(65, 90)).random_int(10, 99);
    }
}
