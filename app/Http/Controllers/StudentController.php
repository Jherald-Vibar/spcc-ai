<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonFile;
use App\Models\LessonProgress;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function dashboard(Request $request): View
    {
        $student = $request->user();

        $subjectIds = Subject::visibleTo($student)->pluck('id');

        $progress = LessonProgress::where('user_id', $student->id)
            ->whereHas('lesson', fn ($q) => $q->published()->whereIn('subject_id', $subjectIds))
            ->get();

        $current = $progress->where('status', 'in_progress')->sortByDesc('updated_at')->first();
        $continue = $current?->lesson()->with('subject')->first();

        return view('student.dashboard', [
            'subjectCount'    => $subjectIds->count(),
            'completed'       => $progress->where('status', 'completed')->count(),
            'inProgress'      => $progress->where('status', 'in_progress')->count(),
            'continue'        => $continue,
            'continueSubject' => $continue?->subject,
        ]);
    }

    /** "My Lessons": pick year + subject, then see the available lessons. */
    public function lessons(Request $request): View
    {
        $student  = $request->user();
        $subjects = Subject::visibleTo($student)->orderBy('year')->orderBy('code')->get();

        $years = $student->year_level
            ? [$student->year_level => Subject::YEARS[$student->year_level] ?? $student->year_level.'th Year']
            : Subject::YEARS;

        $year    = (int) $request->query('year', 0);
        $subject = $subjects->firstWhere('code', strtoupper((string) $request->query('subject', '')));

        // ignore a subject that does not belong to the chosen year
        if ($subject && $year && $subject->year !== $year) {
            $subject = null;
        }

        $lessons = collect();
        if ($subject) {
            $lessons = $subject->lessons()->published()->get();
            Lesson::withStatusFor($lessons, $student);
        }

        return view('student.lessons.index', [
            'years'           => $years,
            'subjects'        => $subjects,
            'selectedYear'    => $year ?: ($subject->year ?? 0),
            'selectedSubject' => $subject,
            'lessons'         => $lessons,
        ]);
    }

    public function showLesson(Request $request, string $subject, int $week): View
    {
        $student = $request->user();

        $subjectModel = Subject::visibleTo($student)->where('code', strtoupper($subject))->firstOrFail();
        $lesson = $subjectModel->lessons()->published()->where('week', $week)->firstOrFail();

        // Opening a lesson for the first time marks it "in progress"; completed stays completed.
        LessonProgress::firstOrCreate(
            ['user_id' => $student->id, 'lesson_id' => $lesson->id],
            ['status' => 'in_progress'],
        );

        $lessons = $subjectModel->lessons()->published()->get();
        Lesson::withStatusFor($lessons, $student);
        $lesson->setAttribute('status', $lessons->firstWhere('id', $lesson->id)->status);

        $tab = $request->query('tab');

        return view('student.lessons.show', [
            'subject'   => $subjectModel,
            'lesson'    => $lesson,
            'lessons'   => $lessons,
            'materials' => $lesson->files()->orderBy('name')->get(),
            // the answers stay on the server: the browser only gets the question and its options
            'questions' => $lesson->quizQuestions()->get(['id', 'question', 'options']),
            'quizStatus' => $lesson->quiz_status,
            'lastAttempt' => $lesson->quizAttempts()->where('user_id', $student->id)->latest()->first(['score', 'total', 'created_at']),
            'posts'     => $lesson->forumPosts()->whereNull('parent_id')
                                ->with(['user:id,name,role', 'replies.user:id,name,role'])
                                ->latest()->limit(50)->get(),
            'tab'       => in_array($tab, ['overview', 'video', 'chat', 'reviewer', 'materials', 'forum'], true)
                            ? $tab : 'overview',
        ]);
    }

    /** Download a lesson file. Only for published lessons of subjects the student may open. */
    public function downloadFile(Request $request, LessonFile $file): StreamedResponse
    {
        $lesson = Lesson::published()->find($file->lesson_id);

        abort_unless(
            $lesson && Subject::visibleTo($request->user())->whereKey($lesson->subject_id)->exists(),
            404
        );
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $file->name);
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
