<?php

namespace App\Http\Controllers;

use App\Models\ForumPost;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Student forum: discussions and replies under a lesson. Same access rule as the lesson page. */
class ForumController extends Controller
{
    public function store(Request $request, string $subject, int $week): RedirectResponse
    {
        $student = $request->user();

        $subjectModel = Subject::visibleTo($student)->where('code', strtoupper($subject))->firstOrFail();
        $lesson = $subjectModel->lessons()->published()->where('week', $week)->firstOrFail();

        $data = $request->validate([
            'body'      => ['required', 'string', 'min:2', 'max:1000'],
            'parent_id' => ['nullable', 'integer'],
        ], [
            'body.required' => 'Write something before posting.',
            'body.min'      => 'Your post is too short.',
            'body.max'      => 'Posts can be at most 1000 characters.',
        ]);

        $parentId = null;
        if (! empty($data['parent_id'])) {
            // a reply must belong to a top-level post of this same lesson (replies do not nest further)
            $parentId = ForumPost::where('lesson_id', $lesson->id)->whereNull('parent_id')->findOrFail($data['parent_id'])->id;
        }

        ForumPost::create([
            'lesson_id' => $lesson->id,
            'user_id'   => $student->id,
            'parent_id' => $parentId,
            'body'      => trim($data['body']),
        ]);

        return redirect()->route('student.lessons.show', [$subjectModel->code, $lesson->week, 'tab' => 'forum']);
    }

    /** Students can remove their own posts (and the replies under them). */
    public function destroy(Request $request, ForumPost $post): RedirectResponse
    {
        abort_unless($post->user_id === $request->user()->id, 403);

        $lesson = $post->lesson()->with('subject')->firstOrFail();
        $post->delete();

        return redirect()->route('student.lessons.show', [$lesson->subject->code, $lesson->week, 'tab' => 'forum']);
    }
}
