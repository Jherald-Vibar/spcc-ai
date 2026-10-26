<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Admin creates subjects and assigns a teacher; teachers then see them under "My Subjects". */
class AdminSubjectController extends Controller
{
    public function index(Request $request): View
    {
        $year = (int) $request->query('year', 0);

        return view('admin.subjects', [
            'subjects' => Subject::with('teacher')->withCount('lessons')
                ->when($year, fn ($q) => $q->where('year', $year))
                ->orderBy('year')->orderBy('code')->get(),
            'teachers' => User::where('role', User::ROLE_TEACHER)->where('status', User::STATUS_ACTIVE)->orderBy('name')->get(['id', 'name']),
            'years'    => Subject::YEARS,
            'year'     => $year,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['code' => Str::upper(trim((string) $request->input('code')))]);
        $data = $this->validated($request);

        $subject = Subject::create($data);

        return redirect()->route('admin.subjects')->with('status', "Subject {$subject->code} created.");
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $request->merge(['code' => Str::upper(trim((string) $request->input('code')))]);
        $subject->update($this->validated($request, $subject));

        return redirect()->route('admin.subjects')->with('status', "Subject {$subject->code} updated.");
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        if ($subject->lessons()->exists()) {
            return back()->withErrors(['status' => "{$subject->code} still has lessons. Remove them first."]);
        }

        $subject->delete();

        return redirect()->route('admin.subjects')->with('status', "Subject {$subject->code} deleted.");
    }

    private function validated(Request $request, ?Subject $subject = null): array
    {
        return $request->validate([
            'code'       => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9-]+$/', Rule::unique('subjects', 'code')->ignore($subject?->id)],
            'name'       => ['required', 'string', 'max:150'],
            'year'       => ['required', 'integer', 'between:1,4'],
            'teacher_id' => ['nullable', Rule::exists('users', 'id')->where('role', User::ROLE_TEACHER)],
        ], [
            'code.regex' => 'Use capital letters and numbers only, e.g. IAS2.',
        ]);
    }
}
