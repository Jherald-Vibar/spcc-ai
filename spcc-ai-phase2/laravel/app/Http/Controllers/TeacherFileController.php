<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessLessonFile;
use App\Models\LessonFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Teacher actions on a single uploaded file. */
class TeacherFileController extends Controller
{
    /** Send a file to the AI service again (e.g. after a failure or after the service was offline). */
    public function reprocess(Request $request, LessonFile $file): RedirectResponse
    {
        $lesson = $file->lesson()->with('subject')->firstOrFail();

        // a teacher may only touch files in their own subjects
        abort_unless($lesson->subject->teacher_id === $request->user()->id, 404);

        $file->update([
            'processing_status' => LessonFile::STATUS_PENDING,
            'processing_error'  => null,
        ]);
        ProcessLessonFile::dispatch($file->id);

        return back()->with('status', "“{$file->name}” was queued for AI processing again.");
    }
}
