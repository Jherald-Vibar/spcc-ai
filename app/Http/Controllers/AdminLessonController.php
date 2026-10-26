<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Lesson;
use App\Models\LessonFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Admin lesson moderation: review, publish, unpublish, remove. */
class AdminLessonController extends Controller
{
    public function index(Request $request): View
    {
        $tab = in_array($request->query('tab'), ['all', 'pending', 'reported'], true) ? $request->query('tab') : 'all';
        $q   = trim((string) $request->query('q', ''));

        $counts = [
            'all'      => Lesson::count(),
            'pending'  => Lesson::draft()->count(),
            'reported' => Lesson::reported()->count(),
        ];

        $lessons = Lesson::with(['subject', 'creator'])
            ->withCount(['reports as open_reports_count' => fn ($r) => $r->whereNull('resolved_at')])
            ->when($tab === 'pending', fn ($query) => $query->draft())
            ->when($tab === 'reported', fn ($query) => $query->reported())
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $query->where(function ($w) use ($like) {
                    $w->where('title', 'like', $like)
                        ->orWhereHas('subject', fn ($s) => $s->where('code', 'like', $like))
                        ->orWhereHas('creator', fn ($c) => $c->where('name', 'like', $like));
                });
            })
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.lessons', compact('tab', 'q', 'counts', 'lessons'));
    }

    public function show(Lesson $lesson): View
    {
        $lesson->load(['subject', 'creator', 'files', 'reports.reporter']);

        return view('admin.lesson-show', compact('lesson'));
    }

    public function publish(Lesson $lesson): RedirectResponse
    {
        $lesson->update(['published_at' => $lesson->published_at ?? now()]);
        ActivityLog::record('lesson_published', 'Lesson published', $lesson->subject->code.' – Week '.$lesson->week);

        return back()->with('status', "Week {$lesson->week} – {$lesson->title} is now published.");
    }

    public function unpublish(Lesson $lesson): RedirectResponse
    {
        $lesson->update(['published_at' => null]);

        return back()->with('status', "Week {$lesson->week} – {$lesson->title} was unpublished.");
    }

    public function dismissReports(Lesson $lesson): RedirectResponse
    {
        $lesson->reports()->whereNull('resolved_at')->update(['resolved_at' => now()]);

        return back()->with('status', 'Reports marked as resolved.');
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $label = $lesson->subject->code.' – Week '.$lesson->week;
        $lesson->deleteWithFiles();
        ActivityLog::record('lesson_removed', 'Lesson removed', $label);

        return redirect()->route('admin.lessons')->with('status', "{$label} was removed.");
    }

    public function downloadFile(LessonFile $file): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $file->name);
    }
}
