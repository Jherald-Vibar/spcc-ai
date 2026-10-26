"""SPCC AI service. Run with ONE worker (ChromaDB is an embedded database):
    uvicorn app.main:app --host 127.0.0.1 --port 8001
"""
from __future__ import annotations

import hmac
import logging
import shutil
import tempfile
from contextlib import asynccontextmanager
from pathlib import Path

from fastapi import Depends, FastAPI, File, Form, Header, HTTPException, UploadFile
from pydantic import BaseModel, Field

from .config import load_settings
from .embeddings import SentenceTransformerEmbedder
from .extractors import SUPPORTED, ExtractionError
from .ingest import FileMeta, ingest_file
from .llm import LLMError, build_llm
from .quiz import NoMaterialError, QuizService
from .rag import RagService
from .store import ChromaStore
from .video_search import VideoSearchError, VideoSearchService

log = logging.getLogger("spcc-ai")
settings = load_settings()
state: dict = {}


@asynccontextmanager
async def lifespan(app: FastAPI):
    if not settings.api_key:
        raise RuntimeError("AI_SERVICE_KEY is not set. Copy .env.example to .env first.")
    log.info("Loading embedding model %s ...", settings.embedding_model)
    embedder = SentenceTransformerEmbedder(settings.embedding_model)
    store = ChromaStore(settings.data_dir / "chroma")
    llm = build_llm(settings)
    if llm is None:
        log.warning("No LLM API key set: /ingest works, /ask will return 503.")
    state.update(
        embedder=embedder,
        store=store,
        rag=RagService(embedder, store, llm, settings.similarity_threshold, settings.top_k),
        quiz=QuizService(store, llm, settings.quiz_max_words),
        videos=VideoSearchService(store, settings.gemini_api_key, settings.gemini_search_model),
        llm_ready=llm is not None,
    )
    yield
    state.clear()


app = FastAPI(title="SPCC AI Service", lifespan=lifespan)


def require_key(x_api_key: str = Header(default="")) -> None:
    if not hmac.compare_digest(x_api_key.encode(), settings.api_key.encode()):
        raise HTTPException(status_code=401, detail="Invalid API key.")


class AskRequest(BaseModel):
    question: str = Field(min_length=2, max_length=1000)
    lesson_ids: list[int] = Field(min_length=1, max_length=60)
    # Optional: lesson title (e.g. "Polyalphabetic Ciphers"). Used only to improve search
    # for short questions like "What is cipher?". Older callers can leave it out.
    lesson_title: str = Field(default="", max_length=200)


class QuizRequest(BaseModel):
    lesson_ids: list[int] = Field(min_length=1, max_length=10)
    count: int = Field(default=5, ge=1, le=10)


class VideoSearchRequest(BaseModel):
    lesson_ids: list[int] = Field(min_length=1, max_length=10)
    lesson_title: str = Field(min_length=2, max_length=200)
    count: int = Field(default=5, ge=1, le=8)


@app.get("/health", dependencies=[Depends(require_key)])
def health() -> dict:
    return {
        "status": "ok",
        "chunks": state["store"].count(),
        "embedding_model": settings.embedding_model,
        "llm": f"{settings.llm_provider}:{settings.llm_model}" if state["llm_ready"] else None,
        "threshold": settings.similarity_threshold,
    }


@app.post("/ingest", dependencies=[Depends(require_key)])
def ingest(
    file: UploadFile = File(...),
    file_id: int = Form(...),
    lesson_id: int = Form(...),
    subject_code: str = Form(...),
    year: int = Form(...),
    week: int = Form(...),
    lesson_title: str = Form(...),
    file_name: str = Form(...),
) -> dict:
    extension = Path(file_name).suffix.lower().lstrip(".")
    if extension not in SUPPORTED:
        raise HTTPException(422, f"Unsupported file type: .{extension}")

    limit = settings.max_upload_mb * 1024 * 1024
    with tempfile.TemporaryDirectory() as tmp:
        target = Path(tmp) / f"upload.{extension}"
        size = 0
        with target.open("wb") as out:
            while chunk := file.file.read(1024 * 1024):
                size += len(chunk)
                if size > limit:
                    raise HTTPException(413, f"File is larger than {settings.max_upload_mb} MB.")
                out.write(chunk)
        meta = FileMeta(file_id, lesson_id, subject_code, year, week, lesson_title, file_name)
        try:
            return ingest_file(target, extension, meta, settings, state["embedder"], state["store"])
        except ExtractionError as exc:
            raise HTTPException(422, str(exc)) from exc


@app.delete("/files/{file_id}", dependencies=[Depends(require_key)])
def delete_file(file_id: int) -> dict:
    state["store"].delete_file(file_id)
    return {"deleted": file_id}


@app.post("/ask", dependencies=[Depends(require_key)])
def ask(req: AskRequest) -> dict:
    if not state["llm_ready"]:
        raise HTTPException(503, "The AI model is not configured on the AI service.")
    try:
        result = state["rag"].ask(req.question, req.lesson_ids, context=req.lesson_title.strip())
    except LLMError as exc:
        log.exception("LLM failure")
        raise HTTPException(502, "The AI model did not respond. Please try again.") from exc
    return {
        "found": result.found,
        "answer": result.answer,
        "top_score": result.top_score,
        "sources": [s.__dict__ for s in result.sources],
    }


@app.post("/quiz", dependencies=[Depends(require_key)])
def quiz(req: QuizRequest) -> dict:
    """Write multiple-choice questions from the processed files of the given lessons only."""
    if not state["llm_ready"]:
        raise HTTPException(503, "The AI model is not configured on the AI service.")
    try:
        questions = state["quiz"].generate(req.lesson_ids, req.count)
    except NoMaterialError as exc:
        raise HTTPException(422, str(exc)) from exc  # 4xx: Laravel will not retry
    except LLMError as exc:
        log.exception("LLM failure while writing a quiz")
        raise HTTPException(502, "The AI model did not respond. Please try again.") from exc
    return {"questions": questions}


@app.post("/video-suggestions", dependencies=[Depends(require_key)])
def video_suggestions(req: VideoSearchRequest) -> dict:
    """Suggest YouTube videos for a lesson (Gemini + Google Search). A teacher must approve one."""
    try:
        videos = state["videos"].suggest(req.lesson_ids, req.lesson_title, req.count)
    except VideoSearchError as exc:
        log.warning("Video search failed: %s", exc)
        raise HTTPException(502, str(exc)) from exc
    return {"videos": videos}
