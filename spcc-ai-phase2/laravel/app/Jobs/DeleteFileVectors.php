<?php

namespace App\Jobs;

use App\Services\AiClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Remove a deleted file's chunks from the vector database. */
class DeleteFileVectors implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public int $fileId)
    {
    }

    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function handle(AiClient $ai): void
    {
        $ai->deleteFile($this->fileId);
    }
}
