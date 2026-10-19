<?php

namespace App\Http\Controllers;

use App\Support\SampleCurriculum;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function dashboard(): View
    {
        $subjects = collect(SampleCurriculum::subjects());
        $lessons  = collect(SampleCurriculum::lessons('IAS2'));

        return view('student.dashboard', [
            'subjectCount' => $subjects->count(),
            'completed'    => $lessons->where('status', 'completed')->count(),
            'inProgress'   => $lessons->where('status', 'in_progress')->count(),
            'continue'     => $lessons->firstWhere('status', 'in_progress'),
            'continueSubject' => SampleCurriculum::subject('IAS2'),
        ]);
    }

    /** "My Lessons": pick year + subject, then see the available lessons. */
    public function lessons(Request $request): View
    {
        $years    = SampleCurriculum::YEARS;
        $subjects = SampleCurriculum::subjects();

        $year    = (int) $request->query('year', 0);
        $subject = SampleCurriculum::subject((string) $request->query('subject', ''));

        // ignore a subject that does not belong to the chosen year
        if ($subject && $year && $subject['year'] !== $year) {
            $subject = null;
        }

        return view('student.lessons.index', [
            'years'           => $years,
            'subjects'        => $subjects,
            'selectedYear'    => $year ?: ($subject['year'] ?? 0),
            'selectedSubject' => $subject,
            'lessons'         => $subject ? SampleCurriculum::lessons($subject['code']) : [],
        ]);
    }

    public function showLesson(Request $request, string $subject, int $week): View
    {
        $tab = $request->query('tab');

        $subjectData = SampleCurriculum::subject($subject);
        $lesson      = $subjectData ? SampleCurriculum::lesson($subject, $week) : null;

        abort_if(! $lesson, 404);

        return view('student.lessons.show', [
            'subject'   => $subjectData,
            'lesson'    => $lesson,
            'lessons'   => SampleCurriculum::lessons($subject),
            'materials' => SampleCurriculum::materials(),
            'questions' => SampleCurriculum::reviewer(),
            'posts'     => SampleCurriculum::forumPosts(),
            'tab'       => in_array($tab, ['overview', 'video', 'chat', 'reviewer', 'materials', 'forum'], true)
                            ? $tab : 'overview',
        ]);
    }

    public function profile(): View
    {
        return view('student.profile');
    }

    public function settings(): View
    {
        return view('student.settings');
    }
}
