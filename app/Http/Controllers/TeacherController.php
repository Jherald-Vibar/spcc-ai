<?php

namespace App\Http\Controllers;

use App\Support\SampleCurriculum;
use App\Support\SampleStaffData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function dashboard(): View
    {
        $lessons = collect(SampleStaffData::teacherLessons('IAS2'));

        return view('teacher.dashboard', [
            'subjectCount' => count(SampleCurriculum::subjects()),
            'published'    => $lessons->where('status', 'published')->count(),
            'drafts'       => $lessons->where('status', 'draft')->count(),
            'recent'       => $lessons->reverse()->take(3)->values(),
            'subject'      => SampleCurriculum::subject('IAS2'),
        ]);
    }

    /** "My Subjects": pick year + subject, then see the subject dashboard. */
    public function subjects(Request $request): View
    {
        $year    = (int) $request->query('year', 0);
        $subject = SampleCurriculum::subject((string) $request->query('subject', ''));

        if ($subject && $year && $subject['year'] !== $year) {
            $subject = null;
        }

        return view('teacher.subjects', [
            'years'           => SampleCurriculum::YEARS,
            'subjects'        => SampleCurriculum::subjects(),
            'selectedYear'    => $year ?: ($subject['year'] ?? 0),
            'selectedSubject' => $subject,
            'lessons'         => $subject ? SampleStaffData::teacherLessons($subject['code']) : [],
            'analytics'       => SampleStaffData::teacherAnalytics(),
        ]);
    }

    public function createLesson(string $subject): View
    {
        $subjectData = SampleCurriculum::subject($subject);
        abort_if(! $subjectData, 404);

        return view('teacher.lessons.create', [
            'subject'  => $subjectData,
            'nextWeek' => count(SampleStaffData::teacherLessons($subject)) + 1,
        ]);
    }

    public function storeLesson(Request $request, string $subject): RedirectResponse
    {
        $subjectData = SampleCurriculum::subject($subject);
        abort_if(! $subjectData, 404);

        $data = $request->validate([
            'week'          => ['required', 'integer', 'min:1', 'max:30'],
            'title'         => ['required', 'string', 'max:150'],
            'description'   => ['nullable', 'string', 'max:2000'],
            'materials'     => ['nullable', 'array', 'max:10'],
            'materials.*'   => ['file', 'mimes:pdf,pptx,docx', 'max:20480'],
            'ai_prompt'     => ['nullable', 'string', 'max:4000'],
            'generate_video' => ['nullable', 'boolean'],
            'action'        => ['required', 'in:draft,publish'],
        ]);

        // TODO: persist the lesson (Lesson::create(...)), store $request->file('materials')
        // and queue the AI video job here. Nothing is saved yet.

        $state = $data['action'] === 'publish' ? 'published' : 'saved as a draft';

        return redirect()
            ->route('teacher.subjects', ['year' => $subjectData['year'], 'subject' => $subjectData['code']])
            ->with('status', "Week {$data['week']} – {$data['title']} {$state}. (Demo only: connect the Lesson model in TeacherController@storeLesson to actually save it.)");
    }

    public function profile(): View
    {
        return view('teacher.profile');
    }

    public function settings(): View
    {
        return view('teacher.settings');
    }
}
