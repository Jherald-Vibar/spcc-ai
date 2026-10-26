<?php

namespace App\Http\Controllers;

use App\Models\QuizAttempt;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Grades the reviewer on the server so the correct answers never sit in the page source. */
class LessonQuizController extends Controller
{
    public function submit(Request $request, string $subject, int $week): JsonResponse
    {
        $student = $request->user();

        $subjectModel = Subject::visibleTo($student)->where('code', strtoupper($subject))->firstOrFail();
        $lesson = $subjectModel->lessons()->published()->where('week', $week)->firstOrFail();

        $data = $request->validate([
            'answers'   => ['required', 'array'],
            'answers.*' => ['nullable', 'integer', 'between:0,5'],
        ]);

        $questions = $lesson->quizQuestions()->get();

        if ($questions->isEmpty()) {
            return response()->json(['message' => 'This lesson has no reviewer questions yet.'], 409);
        }

        $results = [];
        $picked = [];
        $score = 0;

        foreach ($questions as $q) {
            $choice = $data['answers'][$q->id] ?? null;
            $choice = is_numeric($choice) ? (int) $choice : null;
            $correct = $choice !== null && $choice === $q->answer_index;
            $score += $correct ? 1 : 0;
            $picked[$q->id] = $choice;

            $results[] = [
                'id'          => $q->id,
                'correct'     => $correct,
                'picked'      => $choice,
                'answer'      => $q->answer_index,
                'explanation' => $q->explanation,
            ];
        }

        QuizAttempt::create([
            'user_id'   => $student->id,
            'lesson_id' => $lesson->id,
            'score'     => $score,
            'total'     => $questions->count(),
            'answers'   => $picked,
        ]);

        return response()->json(['score' => $score, 'total' => $questions->count(), 'results' => $results]);
    }
}
