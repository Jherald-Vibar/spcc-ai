# SPCC AI: Phase 2 (RAG chat with citations)

Two parts: `ai-service/` (new Python FastAPI service) and `laravel/` (drop-in files for your project).

## What it does
1. A teacher uploads PDF / PPTX / DOCX / PNG / JPG. Laravel stores it and queues `ProcessLessonFile`.
2. The job sends the file to the AI service: extract text (per page / slide / Word section), chunk (~300 words, small slides merged), embed (local multilingual model), store in ChromaDB.
3. A student asks a question in the AI Chat tab. Laravel works out which lessons the student may use (published, own year level) and sends only those lesson ids with the question.
4. The AI service retrieves the best chunks from those lessons only.
   - Guard 1: if no chunk scores above `SIMILARITY_THRESHOLD`, the LLM is **not called**. Answer: "This is not in the lecture materials."
   - Guard 2: the prompt forces the LLM to answer only from the excerpts, or reply `NOT_IN_LECTURE`.
5. The answer shows `[1]`, `[2]` markers and a Sources list (file, page/slide, week) linking to the download.
6. Deleting a file or lesson removes its vectors. Every question is saved in `chat_messages` (teacher analytics later).

## Files to copy into the Laravel project (overwrites 6 files)
New: `app/Services/AiClient.php`, `AiServiceException.php`, `app/Jobs/ProcessLessonFile.php`, `DeleteFileVectors.php`, `app/Console/Commands/ReindexLessonFiles.php`, `app/Http/Controllers/LessonChatController.php`, `TeacherFileController.php`, `app/Models/ChatMessage.php`, 2 migrations.

Overwritten (based on the zip you sent; compare if you changed them since):
| File | Change |
|---|---|
| `app/Models/LessonFile.php` | status fields + events that queue processing and vector deletion |
| `app/Models/Lesson.php` | `deleteWithFiles()` now calls `$file->delete()` per file (1 line) |
| `app/Http/Controllers/TeacherController.php` | allow `png,jpg,jpeg` in validation (1 line) |
| `routes/student.php`, `routes/teacher.php` | chat route, reprocess route |
| `resources/views/student/lessons/partials/chat.blade.php` | real chat UI |
| `resources/views/teacher/lessons/show.blade.php` | AI status chip + "Retry AI" per file |
| `resources/views/teacher/lessons/create.blade.php` | image types in the upload text/accept |

## Setup
Add to `config/services.php` (inside the returned array):
```php
'ai' => [
    'url'            => env('AI_SERVICE_URL', 'http://127.0.0.1:8001'),
    'key'            => env('AI_SERVICE_KEY'),
    'timeout'        => (int) env('AI_SERVICE_TIMEOUT', 60),
    'ingest_timeout' => (int) env('AI_SERVICE_INGEST_TIMEOUT', 600),
],
```
Add to Laravel `.env`:
```
AI_SERVICE_URL=http://127.0.0.1:8001
AI_SERVICE_KEY=the-same-long-random-string-as-ai-service
DB_QUEUE_RETRY_AFTER=1000
```
`DB_QUEUE_RETRY_AFTER` matters: the default (90 s) would make the queue start a second copy of a job that is still embedding.

```bash
# Laravel
php artisan migrate
php artisan ai:reindex          # only if lessons were uploaded before Phase 2

# AI service (Git Bash)
cd ai-service
python -m venv .venv && source .venv/Scripts/activate
pip install -r requirements.txt
cp .env.example .env            # set AI_SERVICE_KEY and ANTHROPIC_API_KEY (or GEMINI_API_KEY)
uvicorn app.main:app --host 127.0.0.1 --port 8001     # keep ONE worker

# check it
curl -H "X-API-Key: <key>" http://127.0.0.1:8001/health
```
Run these together: `npm run dev`, `php artisan serve`, **`php artisan queue:work`**, and uvicorn.

The first start downloads the embedding model (~470 MB, needs internet once). Image files and scanned PDFs need OCR: install Tesseract (Windows: the UB-Mannheim installer, add it to PATH). Without it, those files show "AI failed" with an explanation and text PDFs/PPTX/DOCX still work.

Tests (no extra packages needed beyond python-pptx/python-docx/numpy/pillow): `cd ai-service && python -m unittest discover -s tests -t .`

## Tuning (important before the defense)
`SIMILARITY_THRESHOLD=0.30` is a starting guess for the default model, not a measured value.
Too high: real questions get "not in the lecture". Too low: off-topic questions reach the LLM.
Upload 2-3 real lectures, then ask ~20 in-lecture and ~20 off-topic questions and look at the scores:
```sql
SELECT found, ROUND(top_score,2) AS score, question FROM chat_messages ORDER BY id DESC;
```
Pick a value between the two groups. The prompt guard catches borderline cases either way.

## Known limits
- Follow-ups like "explain more" are searched on their own, so they may return "not in the lecture". Fix later by sending the last turn and rewriting the question.
- Conversation history is not shown again after a page reload (it is saved in `chat_messages`, just not displayed yet).
- Slides that are only images/diagrams have no text to search (OCR for images inside PPTX/DOCX is not included).
- Rotate `AI_SERVICE_KEY` and keep the service on 127.0.0.1; do not expose port 8001.
