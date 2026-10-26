"""Vector stores. ChromaStore is used in production; MemoryStore is for tests."""
from __future__ import annotations

from dataclasses import dataclass, field
from pathlib import Path
from typing import Protocol


@dataclass
class Hit:
    text: str
    score: float            # cosine similarity, 0..1 (higher = more similar)
    meta: dict = field(default_factory=dict)


class VectorStore(Protocol):
    def replace_file(self, file_id: int, ids: list[str], documents: list[str],
                     embeddings: list[list[float]], metadatas: list[dict]) -> None: ...

    def delete_file(self, file_id: int) -> None: ...

    def query(self, vector: list[float], lesson_ids: list[int], k: int) -> list[Hit]: ...

    def lesson_chunks(self, lesson_ids: list[int]) -> list[Hit]: ...

    def count(self) -> int: ...


def _clean_meta(meta: dict) -> dict:
    """Chroma metadata only accepts str/int/float/bool (no None)."""
    return {k: ("" if v is None else v) for k, v in meta.items()}


class ChromaStore:
    def __init__(self, path: Path):
        import chromadb

        path.mkdir(parents=True, exist_ok=True)
        self._client = chromadb.PersistentClient(path=str(path))
        self._col = self._client.get_or_create_collection(
            "lectures", metadata={"hnsw:space": "cosine"}
        )

    def replace_file(self, file_id, ids, documents, embeddings, metadatas) -> None:
        self._col.delete(where={"file_id": file_id})  # re-processing is idempotent
        step = 500
        for i in range(0, len(ids), step):
            self._col.add(
                ids=ids[i:i + step],
                documents=documents[i:i + step],
                embeddings=embeddings[i:i + step],
                metadatas=[_clean_meta(m) for m in metadatas[i:i + step]],
            )

    def delete_file(self, file_id: int) -> None:
        self._col.delete(where={"file_id": file_id})

    def query(self, vector, lesson_ids, k) -> list[Hit]:
        total = self._col.count()
        if not lesson_ids or total == 0:
            return []
        res = self._col.query(
            query_embeddings=[vector],
            n_results=min(k, total),
            where={"lesson_id": {"$in": [int(i) for i in lesson_ids]}},
            include=["documents", "metadatas", "distances"],
        )
        hits = []
        for doc, meta, dist in zip(res["documents"][0], res["metadatas"][0], res["distances"][0]):
            hits.append(Hit(doc, 1.0 - float(dist), dict(meta)))
        return sorted(hits, key=lambda h: h.score, reverse=True)

    def lesson_chunks(self, lesson_ids) -> list[Hit]:
        """Every chunk of the given lessons in reading order (file, then position). Used for quizzes."""
        if not lesson_ids:
            return []
        res = self._col.get(
            where={"lesson_id": {"$in": [int(i) for i in lesson_ids]}},
            include=["documents", "metadatas"],
        )
        hits = [Hit(doc, 1.0, dict(meta)) for doc, meta in zip(res["documents"], res["metadatas"])]
        return sorted(hits, key=lambda h: (h.meta.get("file_id", 0), h.meta.get("chunk_index", 0)))

    def count(self) -> int:
        return self._col.count()


class MemoryStore:
    """Tiny numpy store with the same behaviour, used by the unit tests."""

    def __init__(self):
        self._rows: list[tuple[str, str, list[float], dict]] = []

    def replace_file(self, file_id, ids, documents, embeddings, metadatas) -> None:
        self.delete_file(file_id)
        for i, d, e, m in zip(ids, documents, embeddings, metadatas):
            self._rows.append((i, d, e, m))

    def delete_file(self, file_id: int) -> None:
        self._rows = [r for r in self._rows if r[3].get("file_id") != file_id]

    def query(self, vector, lesson_ids, k) -> list[Hit]:
        import numpy as np

        allowed = {int(i) for i in lesson_ids}
        q = np.asarray(vector, dtype=float)
        hits = []
        for _, doc, emb, meta in self._rows:
            if meta.get("lesson_id") in allowed:
                hits.append(Hit(doc, float(np.dot(q, np.asarray(emb, dtype=float))), dict(meta)))
        return sorted(hits, key=lambda h: h.score, reverse=True)[:k]

    def lesson_chunks(self, lesson_ids) -> list[Hit]:
        allowed = {int(i) for i in lesson_ids}
        hits = [Hit(doc, 1.0, dict(meta)) for _, doc, _, meta in self._rows if meta.get("lesson_id") in allowed]
        return sorted(hits, key=lambda h: (h.meta.get("file_id", 0), h.meta.get("chunk_index", 0)))

    def count(self) -> int:
        return len(self._rows)
