"""Settings, read from environment variables (and a .env file if python-dotenv is installed)."""
from __future__ import annotations

import os
from dataclasses import dataclass
from pathlib import Path
from typing import Mapping


def _bool(value: str | None, default: bool) -> bool:
    if value is None or value == "":
        return default
    return value.strip().lower() in {"1", "true", "yes", "on"}


@dataclass(frozen=True)
class Settings:
    api_key: str
    data_dir: Path
    embedding_model: str
    similarity_threshold: float
    top_k: int
    chunk_words: int
    chunk_overlap: int
    chunk_min_words: int
    llm_provider: str
    llm_model: str
    anthropic_api_key: str
    gemini_api_key: str
    ocr_enabled: bool
    ocr_lang: str
    max_upload_mb: int


DEFAULT_MODELS = {"anthropic": "claude-haiku-5-5", "gemini": "gemini-2.5-flash"}


def load_settings(env: Mapping[str, str] | None = None) -> Settings:
    if env is None:
        try:
            from dotenv import load_dotenv

            load_dotenv()
        except ImportError:  # .env loading is optional
            pass
        env = os.environ

    provider = env.get("LLM_PROVIDER", "anthropic").strip().lower()
    return Settings(
        api_key=env.get("AI_SERVICE_KEY", ""),
        data_dir=Path(env.get("DATA_DIR", "./data")),
        embedding_model=env.get("EMBEDDING_MODEL", "paraphrase-multilingual-MiniLM-L12-v2"),
        similarity_threshold=float(env.get("SIMILARITY_THRESHOLD", "0.30")),
        top_k=int(env.get("TOP_K", "5")),
        chunk_words=int(env.get("CHUNK_WORDS", "300")),
        chunk_overlap=int(env.get("CHUNK_OVERLAP", "40")),
        chunk_min_words=int(env.get("CHUNK_MIN_WORDS", "60")),
        llm_provider=provider,
        llm_model=env.get("LLM_MODEL") or DEFAULT_MODELS.get(provider, ""),
        anthropic_api_key=env.get("ANTHROPIC_API_KEY", ""),
        gemini_api_key=env.get("GEMINI_API_KEY", ""),
        ocr_enabled=_bool(env.get("OCR_ENABLED"), True),
        ocr_lang=env.get("OCR_LANG", "eng"),
        max_upload_mb=int(env.get("MAX_UPLOAD_MB", "25")),
    )
