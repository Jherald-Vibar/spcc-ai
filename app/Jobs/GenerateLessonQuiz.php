<?php

namespace App\Jobs;

use App\Models\Lesson;
use App\Models\LessonFile;
use App\Models\QuizQuestion;
use App\Services\AiClient;
use App\Services\AiServiceException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/** Write the lesson's reviewer (5 multiple-choice questions) from its processed files, using the AI service. */
class GenerateLessonQuiz implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public const QUESTIONS = 5;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(public int $lessonId)
    {
    }

    public function backoff(): array
    {
        return [30, 120];
    }

    /** One run per lesson at a time (several files finishing together would otherwise race). */
    public function middleware(): array
    {
        return [(new WithoutOverlapping((string) $this->lessonId))->releaseAfter(30)->expireAfter(600)];
    }

    public function handle(AiClient $ai): void
    {
        $lesson = Lesson::find($this->lessonId);

        if (! $lesson) {
            return;
        }

        $files = $lesson->files()->get(['id', 'processing_status']);

        // no usable material left: nothing to write a quiz from
        if ($files->where('processing_status', LessonFile::STATUS_READY)->isEmpty()) {
            if ($files->whereIn('processing_status', [LessonFile::STATUS_PENDING, LessonFile::STATUS_PROCESSING])->isEmpty()) {
                DB::transaction(function () use ($lesson) {
                    $lesson->quizQuestions()->delete();
                    $lesson->update(['quiz_status' => 'none', 'quiz_error' => null]);
                });
            }

            return;
        }

        // another file of this lesson is still being processed: its own job will trigger the quiz when it finishes
        if ($files->whereIn('processing_status', [LessonFile::STATUS_PENDING, LessonFile::STATUS_PROCESSING])->isNotEmpty()) {
            return;
        }

        $lesson->update(['quiz_status' => 'generating', 'quiz_error' => null]);

        try {
            $items = $ai->quiz($lesson->id, self::QUESTIONS);
        } catch (AiServiceException $e) {
            if (! $e->retryable) {
                $this->fail($e);

                return;
            }

            throw $e;
        }

        $questions = $this->clean($items);

        if ($questions === []) {
            $this->fail(new \RuntimeException('The AI did not return usable questions for this lesson.'));

            return;
        }

        DB::transaction(function () use ($lesson, $questions) {
            $lesson->quizQuestions()->delete();

            foreach ($questions as $i => $q) {
                QuizQuestion::create($q + ['lesson_id' => $lesson->id, 'position' => $i + 1]);
            }

            $lesson->update(['quiz_status' => 'ready', 'quiz_error' => null]);
        });
    }

    public function failed(Throwable $e): void
    {
        // keep the old questions if there were any; only record that the refresh failed
        Lesson::whereKey($this->lessonId)->update([
            'quiz_status' => 'failed',
            'quiz_error'  => Str::limit($e->getMessage(), 280, ''),
        ]);
    }

    /**
     * Never trust model output: keep only well-formed questions (text, 2-6 options, a valid answer index).
     *
     * @return array<int, array{question: string, options: array<int,string>, answer_index: int, explanation: ?string}>
     */
    private function clean(array $items): array
    {
        $out = [];
        $seen = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $question = trim((string) ($item['question'] ?? ''));
            $options = array_map(fn ($o) => trim((string) $o), array_values((array) ($item['options'] ?? [])));
            $answer = $item['answer_index'] ?? null;

            if ($question === '' || count($options) < 2 || count($options) > 6 || in_array('', $options, true)) {
                continue;
            }
            if (! is_int($answer) || $answer < 0 || $answer >= count($options)) {
                continue;
            }
            if (count(array_unique(array_map('mb_strtolower', $options))) !== count($options)) {
                continue; // two identical options
            }

            $key = mb_strtolower($question);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $explanation = trim((string) ($item['explanation'] ?? ''));

            $out[] = [
                'question'     => Str::limit($question, 500, ''),
                'options'      => array_map(fn ($o) => Str::limit($o, 300, ''), $options),
                'answer_index' => $answer,
                'explanation'  => $explanation !== '' ? Str::limit($explanation, 500, '') : null,
            ];

            if (count($out) === self::QUESTIONS) {
                break;
            }
        }

        return $out;
    }
}
