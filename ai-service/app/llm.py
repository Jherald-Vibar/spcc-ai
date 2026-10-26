"""LLM providers. Both only need an API key; swap with LLM_PROVIDER in .env."""
from __future__ import annotations

import os
import time
from typing import Protocol

from .config import Settings


class LLMError(Exception):
    retryable = False


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
            raise LLMError(f"Anthropic request failed: {exc!r}") from exc
        return "".join(b.text for b in msg.content if getattr(b, "type", "") == "text").strip()


class GeminiLLM:
    RETRYABLE_STATUS = (429, 500, 502, 503, 504)
    ATTEMPTS = 4
    BACKOFF = (3, 8, 15)  # seconds to wait after attempt 1, 2, 3

    def __init__(self, api_key: str, model: str, fallback_model: str = ""):
        self._key = api_key
        self._model = model
        self._fallback = fallback_model if fallback_model and fallback_model != model else ""

    def complete(self, system: str, user: str, max_tokens: int = 800) -> str:
        try:
            return self._call(self._model, system, user, max_tokens)
        except LLMError as primary_exc:
            if not self._fallback or not primary_exc.retryable:
                raise
            # main model is overloaded/unavailable: try the fallback model
            return self._call(self._fallback, system, user, max_tokens, attempts=2)

    def _call(self, model: str, system: str, user: str, max_tokens: int, attempts: int = 0) -> str:
        import httpx

        attempts = attempts or self.ATTEMPTS
        url = f"https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent"

        generation_config: dict = {"maxOutputTokens": max_tokens, "temperature": 0.2}
        # gemini-2.5-flash "thinks" by default, which is slow and eats into maxOutputTokens.
        # Turning it off is allowed for flash models only (not for 2.5-pro).
        if "2.5-flash" in model:
            generation_config["thinkingConfig"] = {"thinkingBudget": 0}

        body = {
            "systemInstruction": {"parts": [{"text": system}]},
            "contents": [{"role": "user", "parts": [{"text": user}]}],
            "generationConfig": generation_config,
        }
        timeout = httpx.Timeout(60.0, connect=10.0)
        last_exc: Exception | None = None

        for attempt in range(attempts):
            try:
                r = httpx.post(
                    url,
                    json=body,
                    headers={"x-goog-api-key": self._key},
                    timeout=timeout,
                )
                if r.status_code in self.RETRYABLE_STATUS:
                    raise httpx.HTTPStatusError("retryable", request=r.request, response=r)
                r.raise_for_status()
                data = r.json()
                candidate = data["candidates"][0]
                parts = candidate.get("content", {}).get("parts", [])
                text = "".join(p.get("text", "") for p in parts).strip()
                if not text:
                    reason = candidate.get("finishReason", "unknown")
                    raise ValueError(f"empty reply (finishReason={reason}); try a larger max_tokens")
                return text
            except (httpx.TimeoutException, httpx.NetworkError) as exc:
                last_exc = exc  # temporary: retry
            except httpx.HTTPStatusError as exc:
                last_exc = exc
                if exc.response.status_code not in self.RETRYABLE_STATUS:
                    # e.g. 400/401/403/404: retrying will not help
                    raise LLMError(f"Gemini {model} request failed: {exc!r}") from exc
            except Exception as exc:  # bad JSON, blocked response, empty reply
                raise LLMError(f"Gemini {model} returned an unexpected response: {exc!r}") from exc

            if attempt < attempts - 1:
                time.sleep(self.BACKOFF[min(attempt, len(self.BACKOFF) - 1)])

        err = LLMError(f"Gemini {model} request failed after retries: {last_exc!r}")
        err.retryable = True  # tells complete() a fallback model is worth trying
        raise err from last_exc


def build_llm(settings: Settings) -> LLM | None:
    """Returns None when no API key is set; ingestion still works, /ask answers 503."""
    if settings.llm_provider == "anthropic" and settings.anthropic_api_key:
        return AnthropicLLM(settings.anthropic_api_key, settings.llm_model)
    if settings.llm_provider == "gemini" and settings.gemini_api_key:
        fallback = getattr(settings, "llm_fallback_model", "") or os.environ.get("LLM_FALLBACK_MODEL", "")
        return GeminiLLM(settings.gemini_api_key, settings.llm_model, fallback)
    return None