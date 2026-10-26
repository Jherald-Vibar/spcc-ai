"""File -> segments -> chunks -> embeddings -> vector store."""
from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path

from .chunker import chunk_segments
from .config import Settings
from .embeddings import Embedder
from .extractors import ExtractionError, extract
from .store import VectorStore


@dataclass
class FileMeta:
    file_id: int
    lesson_id: int
    subject_code: str
    year: int
    week: int
    lesson_title: str
    file_name: str


def ingest_file(path: Path, extension: str, meta: FileMeta, settings: Settings,
                embedder: Embedder, store: VectorStore) -> dict:
    segments = extract(path, extension, ocr=settings.ocr_enabled, ocr_lang=settings.ocr_lang)
    chunks = chunk_segments(
        segments, settings.chunk_words, settings.chunk_overlap, settings.chunk_min_words
    )
    if not chunks:
        raise ExtractionError("No readable text was found in this file.")

    embeddings = embedder.embed_documents([c.text for c in chunks])
    ids = [f"{meta.file_id}-{i}" for i in range(len(chunks))]
    metadatas = [
        {
            "file_id": meta.file_id,
            "lesson_id": meta.lesson_id,
            "subject_code": meta.subject_code,
            "year": meta.year,
            "week": meta.week,
            "lesson_title": meta.lesson_title,
            "file_name": meta.file_name,
            "unit": c.unit,
            "start": c.start,
            "end": c.end,
            "label": c.label,
            "chunk_index": i,
        }
        for i, c in enumerate(chunks)
    ]
    store.replace_file(meta.file_id, ids, [c.text for c in chunks], embeddings, metadatas)
    return {"file_id": meta.file_id, "chunks": len(chunks), "units": len(segments)}
