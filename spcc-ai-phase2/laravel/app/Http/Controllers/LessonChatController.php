<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\LessonFile;
use App\Models\Subject;
use App\Services\AiClient;
use App\Services\AiServiceException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Student AI chat. Laravel decides WHICH lessons the question may use (published lessons of the
 * student's own year level); the AI service only searches inside that list.
 */
class LessonChatController extends Controller
{
    public const BUSY_MESSAGE = 'The AI assistant is not available right now. Please try again in a moment.';

    public function __invoke(Request $request, string $subject, int $week, AiClient $ai): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'min:2', 'max:500'],
            'scope'    => ['nullable', 'in:lesson,subject'],
        ]);

        $student = $request->user();

        // same access rule as the lesson page itself
        $subjectModel = Subject::visibleTo($student)->where('code', strtoupper($subject))->firstOrFail();
        $lesson = $subjectModel->lessons()->published()->where('week', $week)->firstOrFail();

        $scope = $data['scope'] ?? 'lesson';
        $lessonIds = $scope === 'subject'
            ? $subjectModel->lessons()->published()->pluck('id')->all()
            : [$lesson->id];

        $question = trim($data['question']);

        try {
            $result = $ai->ask($question, $lessonIds);
        } catch (AiServiceException $e) {
            Log::warning('AI chat failed: '.$e->getMessage());

            return response()->json(['message' => self::BUSY_MESSAGE], 503);
        }

        $sources = $this->checkedSources($result['sources'] ?? [], $lessonIds, (bool) ($result['found'] ?? false));

        ChatMessage::create([
            'user_id'   => $student->id,
            'lesson_id' => $lesson->id,
            'scope'     => $scope,
            'question'  => $question,
            'answer'    => (string) ($result['answer'] ?? ''),
            'found'     => (bool) ($result['found'] ?? false),
            'top_score' => $result['top_score'] ?? null,
            'sources'   => $sources,
        ]);

        return response()->json([
            'found'   => (bool) ($result['found'] ?? false),
            'answer'  => (string) ($result['answer'] ?? ''),
            'sources' => $sources,
        ]);
    }

    /**
     * Keep only sources whose file still exists inside the allowed lessons (protects against stale vectors)
     * and add a download link the student is allowed to use.
     */
    private function checkedSources(array $sources, array $lessonIds, bool $found): array
    {
        if (! $found || $sources === []) {
            return [];
        }

        $files = LessonFile::whereIn('id', collect($sources)->pluck('file_id'))
            ->whereIn('lesson_id', $lessonIds)
            ->get()
            ->keyBy('id');

        return collect($sources)
            ->filter(fn ($s) => $files->has($s['file_id'] ?? 0))
            ->map(fn ($s) => [
                'index'        => (int) $s['index'],
                'file_name'    => (string) $s['file_name'],
                'lesson_title' => (string) $s['lesson_title'],
                'week'         => (int) $s['week'],
                'locator'      => (string) $s['locator'],
                'snippet'      => (string) ($s['snippet'] ?? ''),
                'url'          => route('student.files.download', $files[$s['file_id']]),
            ])
            ->values()
            ->all();
    }
}
