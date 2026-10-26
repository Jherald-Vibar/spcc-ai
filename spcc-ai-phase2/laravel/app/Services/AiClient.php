<?php

namespace App\Services;

use App\Models\LessonFile;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/** Talks to the Python (FastAPI) AI service. Laravel stays the only place that decides who may see what. */
class AiClient
{
    private function http(int $timeout): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.ai.url'), '/'))
            ->withHeaders(['X-API-Key' => (string) config('services.ai.key')])
            ->acceptJson()
            ->timeout($timeout);
    }

    /** Send a stored file to be extracted, chunked, embedded and stored. Returns ['chunks' => n, ...]. */
    public function ingest(LessonFile $file, string $absolutePath): array
    {
        $lesson = $file->lesson;
        $subject = $lesson->subject;

        $stream = @fopen($absolutePath, 'r');
        if ($stream === false) {
            throw new AiServiceException('The stored file could not be found on the server.', false);
        }

        try {
            $response = $this->http((int) config('services.ai.ingest_timeout', 600))
                ->attach('file', $stream, $file->name)
                ->post('/ingest', [
                    'file_id'      => (string) $file->id,
                    'lesson_id'    => (string) $lesson->id,
                    'subject_code' => $subject->code,
                    'year'         => (string) $subject->year,
                    'week'         => (string) $lesson->week,
                    'lesson_title' => $lesson->title,
                    'file_name'    => $file->name,
                ]);
        } catch (ConnectionException $e) {
            throw new AiServiceException('The AI service is not reachable.', true, $e);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $this->throwIfFailed($response);

        return $response->json() ?? [];
    }

    /**
     * Ask a question using only the given lessons.
     *
     * @param  int[]  $lessonIds
     * @return array{found: bool, answer: string, top_score: ?float, sources: array<int, array>}
     */
    public function ask(string $question, array $lessonIds): array
    {
        try {
            $response = $this->http((int) config('services.ai.timeout', 60))
                ->post('/ask', ['question' => $question, 'lesson_ids' => array_values($lessonIds)]);
        } catch (ConnectionException $e) {
            throw new AiServiceException('The AI service is not reachable.', true, $e);
        }

        $this->throwIfFailed($response);

        return $response->json();
    }

    public function deleteFile(int $fileId): void
    {
        try {
            $response = $this->http(30)->delete("/files/{$fileId}");
        } catch (ConnectionException $e) {
            throw new AiServiceException('The AI service is not reachable.', true, $e);
        }

        $this->throwIfFailed($response);
    }

    public function health(): array
    {
        try {
            $response = $this->http(10)->get('/health');
        } catch (ConnectionException $e) {
            throw new AiServiceException('The AI service is not reachable.', true, $e);
        }

        $this->throwIfFailed($response);

        return $response->json();
    }

    private function throwIfFailed(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $detail = $response->json('detail');
        $message = is_string($detail) && $detail !== ''
            ? $detail
            : "The AI service returned an error ({$response->status()}).";

        // 5xx / timeouts / rate limits may succeed later; 4xx (bad file, bad key) will not.
        $retryable = $response->serverError() || in_array($response->status(), [408, 429], true);

        throw new AiServiceException($message, $retryable);
    }
}
