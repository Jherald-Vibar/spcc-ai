"""LLM providers. Both only need an API key; swap with LLM_PROVIDER in .env."""
from __future__ import annotations

from typing import Protocol

from .config import Settings


class LLMError(Exception):
    pass


class LLM(Protocol):
    def complete(self, system: str, user: str, max_tokens: int = 800) -> str: ...


class AnthropicLLM:
    def __init__(self, api_key: str, model: str):
        import anthropic

        self._client = anthropic.Anthropic(api_key=api_key, timeout=60)
        self._model = model

    def complete(self, system: str, user: str, max_tokens: int = 800) -> str:
        try:
            msg = self._client.messages.create(
                model=self._model,
                max_tokens=max_tokens,
                temperature=0.2,
                system=system,
                messages=[{"role": "user", "content": user}],
            )
        except Exception as exc:
            raise LLMError(f"Anthropic request failed: {exc}") from exc
        return "".join(b.text for b in msg.content if getattr(b, "type", "") == "text").strip()


class GeminiLLM:
    def __init__(self, api_key: str, model: str):
        self._key = api_key
        self._model = model

    def complete(self, system: str, user: str, max_tokens: int = 800) -> str:
        import httpx

        url = f"https://generativelanguage.googleapis.com/v1beta/models/{self._model}:generateContent"
        body = {
            "systemInstruction": {"parts": [{"text": system}]},
            "contents": [{"role": "user", "parts": [{"text": user}]}],
            "generationConfig": {"maxOutputTokens": max_tokens, "temperature": 0.2},
        }
        try:
            r = httpx.post(url, json=body, headers={"x-goog-api-key": self._key}, timeout=60)
            r.raise_for_status()
            parts = r.json()["candidates"][0]["content"]["parts"]
        except Exception as exc:
            raise LLMError(f"Gemini request failed: {exc}") from exc
        return "".join(p.get("text", "") for p in parts).strip()


def build_llm(settings: Settings) -> LLM | None:
    """Returns None when no API key is set; ingestion still works, /ask answers 503."""
    if settings.llm_provider == "anthropic" and settings.anthropic_api_key:
        return AnthropicLLM(settings.anthropic_api_key, settings.llm_model)
    if settings.llm_provider == "gemini" and settings.gemini_api_key:
        return GeminiLLM(settings.gemini_api_key, settings.llm_model)
    return None
