<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Lesson;
use App\Models\LessonFile;
use App\Models\LessonProgress;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Teachers manage lessons and materials for the subjects an admin assigned to them. */
class TeacherController extends Controller
{
    public function dashboard(Request $request): View
    {
        $subjects = $this->mySubjects($request)->get();
        $lessons  = Lesson::whereIn('subject_id', $subjects->pluck('id'));

        return view('teacher.dashboard', [
            'subjectCount' => $subjects->count(),
            'published'    => (clone $lessons)->published()->count(),
            'drafts'       => (clone $lessons)->draft()->count(),
            'recent'       => (clone $lessons)->with('subject')->latest('updated_at')->limit(3)->get(),
            'subject'      => $subjects->first(),   // used by the "Create Lesson" shortcut; null if none assigned
        ]);
    }

    /** "My Subjects": pick year + subject, then see the subject dashboard. */
    public function subjects(Request $request): View
    {
        $subjects = $this->mySubjects($request)->orderBy('year')->orderBy('code')->get();

        $year    = (int) $request->query('year', 0);
        $subject = $subjects->firstWhere('code', strtoupper((string) $request->query('subject', '')));

        if ($subject && $year && $subject->year !== $year) {
            $subject = null;
        }

        $lessons   = collect();
        $analytics = [];

        if ($subject) {
            $lessons = $subject->lessons()
                ->withCount(['reports as open_reports_count' => fn ($r) => $r->whereNull('resolved_at')])
                ->get();
            $analytics = $this->analytics($subject, $lessons);
        }

        return view('teacher.subjects', [
            'years'           => Subject::YEARS,
            'subjects'        => $subjects,
            'selectedYear'    => $year ?: ($subject->year ?? 0),
            'selectedSubject' => $subject,
            'lessons'         => $lessons,
            'analytics'       => $analytics,
        ]);
    }

    public function createLesson(Request $request, string $subject): View
    {
        $subjectModel = $this->subjectOrFail($request, $subject);

        return view('teacher.lessons.create', [
            'subject'  => $subjectModel,
            'lesson'   => null,
            'nextWeek' => (int) Lesson::where('subject_id', $subjectModel->id)->max('week') + 1,
        ]);
    }

    public function storeLesson(Request $request, string $subject): RedirectResponse
    {
        $subjectModel = $this->subjectOrFail($request, $subject);
        $data = $this->validated($request, $subjectModel);

        $lesson = DB::transaction(function () use ($request, $subjectModel, $data) {
            $lesson = $subjectModel->lessons()->create([
                'created_by'      => $request->user()->id,
                'week'            => $data['week'],
                'title'           => trim($data['title']),
                'description'     => $data['description'] ?? null,
                'ai_prompt'       => $data['ai_prompt'] ?? null,
                'video_requested' => (bool) ($data['generate_video'] ?? false),
                'published_at'    => $data['action'] === 'publish' ? now() : null,
            ]);

            $this->storeFiles($lesson, $request->file('materials', []));

            return $lesson;
        });

        if ($lesson->isPublished()) {
            ActivityLog::record('lesson_published', 'New lesson published', "{$subjectModel->code} – Week {$lesson->week}");
        }

        return $this->backToSubject($subjectModel)
            ->with('status', "Week {$lesson->week} – {$lesson->title} ".($lesson->isPublished() ? 'published.' : 'saved as a draft.'));
    }

    public function showLesson(Request $request, string $subject, int $week): View
    {
        $subjectModel = $this->subjectOrFail($request, $subject);
        $lesson = $subjectModel->lessons()->where('week', $week)->with('files')->firstOrFail();

        return view('teacher.lessons.show', ['subject' => $subjectModel, 'lesson' => $lesson]);
    }

    public function editLesson(Request $request, string $subject, int $week): View
    {
        $subjectModel = $this->subjectOrFail($request, $subject);
        $lesson = $subjectModel->lessons()->where('week', $week)->with('files')->firstOrFail();

        return view('teacher.lessons.create', [
            'subject'  => $subjectModel,
            'lesson'   => $lesson,
            'nextWeek' => $lesson->week,
        ]);
    }

    public function updateLesson(Request $request, string $subject, int $week): RedirectResponse
    {
        $subjectModel = $this->subjectOrFail($request, $subject);
        $lesson = $subjectModel->lessons()->where('week', $week)->firstOrFail();
        $data = $this->validated($request, $subjectModel, $lesson);

        $wasPublished = $lesson->isPublished();

        DB::transaction(function () use ($request, $lesson, $data) {
            $lesson->update([
                'week'            => $data['week'],
                'title'           => trim($data['title']),
                'description'     => $data['description'] ?? null,
                'ai_prompt'       => $data['ai_prompt'] ?? null,
                'video_requested' => $lesson->video_requested || (bool) ($data['generate_video'] ?? false),
                // keep the original publish date when it stays published
                'published_at'    => $data['action'] === 'publish' ? ($lesson->published_at ?? now()) : null,
            ]);

            // only files that belong to this lesson can be removed
            $remove = $lesson->files()->whereIn('id', $data['remove_files'] ?? [])->get();
            foreach ($remove as $file) {
                Storage::disk('local')->delete($file->path);
                $file->delete();
            }

            $this->storeFiles($lesson, $request->file('materials', []));
        });

        $lesson->refresh();

        if ($lesson->isPublished() && ! $wasPublished) {
            ActivityLog::record('lesson_published', 'Lesson published', "{$subjectModel->code} – Week {$lesson->week}");
        } else {
            ActivityLog::record('lesson_updated', 'Lesson updated', "{$subjectModel->code} – Week {$lesson->week}");
        }

        return $this->backToSubject($subjectModel)
            ->with('status', "Week {$lesson->week} – {$lesson->title} updated.");
    }

    /** Publish a draft or move a published lesson back to draft. */
    public function togglePublish(Request $request, string $subject, int $week): RedirectResponse
    {
        $subjectModel = $this->subjectOrFail($request, $subject);
        $lesson = $subjectModel->lessons()->where('week', $week)->firstOrFail();

        if ($lesson->isPublished()) {
            $lesson->update(['published_at' => null]);
            $message = "Week {$lesson->week} moved back to draft.";
        } else {
            $lesson->update(['published_at' => now()]);
            ActivityLog::record('lesson_published', 'Lesson published', "{$subjectModel->code} – Week {$lesson->week}");
            $message = "Week {$lesson->week} – {$lesson->title} published.";
        }

        return back()->with('status', $message);
    }

    public function destroyLesson(Request $request, string $subject, int $week): RedirectResponse
    {
        $subjectModel = $this->subjectOrFail($request, $subject);
        $lesson = $subjectModel->lessons()->where('week', $week)->firstOrFail();

        $label = "Week {$lesson->week} – {$lesson->title}";
        $lesson->deleteWithFiles();
        ActivityLog::record('lesson_removed', 'Lesson deleted', "{$subjectModel->code} – Week {$week}");

        return $this->backToSubject($subjectModel)->with('status', "{$label} deleted.");
    }

    public function downloadFile(Request $request, LessonFile $file): StreamedResponse
    {
        $lesson = Lesson::findOrFail($file->lesson_id);
        abort_unless($this->mySubjects($request)->whereKey($lesson->subject_id)->exists(), 404);
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $file->name);
    }

    public function profile(): View
    {
        return view('teacher.profile');
    }

    public function settings(): View
    {
        return view('teacher.settings');
    }

    /* ---------------------------------------------------------------- helpers */

    private function mySubjects(Request $request)
    {
        return Subject::where('teacher_id', $request->user()->id);
    }

    /** A teacher can only open subjects assigned to them. */
    private function subjectOrFail(Request $request, string $code): Subject
    {
        return $this->mySubjects($request)->where('code', strtoupper($code))->firstOrFail();
    }

    private function backToSubject(Subject $subject): RedirectResponse
    {
        return redirect()->route('teacher.subjects', ['year' => $subject->year, 'subject' => $subject->code]);
    }

    private function validated(Request $request, Subject $subject, ?Lesson $lesson = null): array
    {
        return $request->validate([
            'week'            => ['required', 'integer', 'min:1', 'max:30',
                                  Rule::unique('lessons', 'week')->where('subject_id', $subject->id)->ignore($lesson?->id)],
            'title'           => ['required', 'string', 'max:150'],
            'description'     => ['nullable', 'string', 'max:2000'],
            'materials'       => ['nullable', 'array', 'max:10'],
            'materials.*'     => ['file', 'mimes:pdf,pptx,docx,png,jpg,jpeg', 'max:20480'],
            'remove_files'    => ['nullable', 'array'],
            'remove_files.*'  => ['integer'],
            'ai_prompt'       => ['nullable', 'string', 'max:4000'],
            'generate_video'  => ['nullable', 'boolean'],
            'action'          => ['required', 'in:draft,publish'],
        ], [
            'week.unique' => 'This subject already has a lesson for that week. Pick another week or edit the existing lesson.',
        ]);
    }

    /** Store uploads on the private disk (not web-accessible) and record them. */
    private function storeFiles(Lesson $lesson, array $uploads): void
    {
        foreach ($uploads as $upload) {
            $lesson->files()->create([
                'name'      => $upload->getClientOriginalName(),
                'path'      => $upload->store("lesson-files/{$lesson->id}", 'local'),
                'extension' => strtolower($upload->getClientOriginalExtension()),
                'bytes'     => $upload->getSize(),
            ]);
        }
    }

    /** Real numbers for the Analytics tab. */
    private function analytics(Subject $subject, $lessons): array
    {
        $students  = User::where('role', User::ROLE_STUDENT)->where('year_level', $subject->year)->count();
        $published = $lessons->filter->isPublished();

        $completed = LessonProgress::whereIn('lesson_id', $published->pluck('id'))->where('status', 'completed')->count();
        $possible  = $students * $published->count();

        return [
            ['label' => 'Enrolled students (this year level)', 'value' => (string) $students],
            ['label' => 'Avg. lesson completion', 'value' => $possible ? round($completed / $possible * 100).'%' : '—'],
            ['label' => 'Published lessons', 'value' => (string) $published->count()],
            ['label' => 'Drafts', 'value' => (string) ($lessons->count() - $published->count())],
        ];
    }
}
