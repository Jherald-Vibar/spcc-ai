"""LLM providers. Both only need an API key; swap with LLM_PROVIDER in .env."""
from __future__ import annotations

import logging
import os
import re
import time
from typing import Protocol

from .config import DEFAULT_MODELS, Settings

log = logging.getLogger("spcc-ai")


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


class OllamaLLM:
    """A model running on this computer through Ollama (https://ollama.com). No API key, no per-request cost.

    num_ctx matters: Ollama's default window (2048 tokens) silently cuts long prompts, which would drop
    most of the lecture excerpts, so it is raised here.
    """

    def __init__(self, base_url: str, model: str, num_ctx: int = 8192, timeout: int = 300, client=None):
        self._url = base_url.rstrip("/") + "/api/chat"
        self._model, self._num_ctx, self._timeout = model, num_ctx, timeout
        self._client = client  # injectable for tests

    def complete(self, system: str, user: str, max_tokens: int = 800) -> str:
        body = {
            "model": self._model,
            "stream": False,
            "messages": [{"role": "system", "content": system}, {"role": "user", "content": user}],
            "options": {"temperature": 0.2, "num_predict": max_tokens, "num_ctx": self._num_ctx},
        }
        try:
            if self._client is None:
                import httpx

                self._client = httpx.Client(timeout=httpx.Timeout(self._timeout, connect=10.0))
            r = self._client.post(self._url, json=body)
            if r.status_code == 404:
                raise LLMError(f"Ollama does not have the model '{self._model}'. Run: ollama pull {self._model}")
            r.raise_for_status()
            text = r.json()["message"]["content"]
        except LLMError:
            raise
        except Exception as exc:
            raise LLMError(f"Ollama request failed (is Ollama running at {self._url}?): {exc!r}") from exc
        # some models print their reasoning in <think> tags: students should only see the answer
        return re.sub(r"<think>.*?</think>", "", text, flags=re.DOTALL).strip()


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


class FallbackLLM:
    """Use `primary` (e.g. Ollama on this PC). If it fails or times out, answer with `fallback` (e.g. Gemini)."""

    def __init__(self, primary: LLM, fallback: LLM, primary_name: str = "primary", fallback_name: str = "fallback"):
        self.primary, self.fallback = primary, fallback
        self.primary_name, self.fallback_name = primary_name, fallback_name

    def complete(self, system: str, user: str, max_tokens: int = 800) -> str:
        try:
            return self.primary.complete(system, user, max_tokens)
        except LLMError as exc:
            log.warning("%s failed (%s); using %s instead", self.primary_name, exc, self.fallback_name)
            return self.fallback.complete(system, user, max_tokens)


def _build_one(provider: str, model: str, settings: Settings) -> LLM | None:
    if provider == "anthropic" and settings.anthropic_api_key:
        return AnthropicLLM(settings.anthropic_api_key, model)
    if provider == "gemini" and settings.gemini_api_key:
        fallback = getattr(settings, "llm_fallback_model", "") or os.environ.get("LLM_FALLBACK_MODEL", "")
        return GeminiLLM(settings.gemini_api_key, model, fallback)
    if provider == "ollama":
        return OllamaLLM(settings.ollama_url, model, settings.ollama_num_ctx, settings.ollama_timeout)
    return None


def build_llm(settings: Settings) -> LLM | None:
    """Returns None when no provider is usable (no API key); ingestion still works, /ask answers 503.
    With LLM_FALLBACK set (gemini or anthropic), the main provider is wrapped so a failure switches to it."""
    main = _build_one(settings.llm_provider, settings.llm_model, settings)
    backup_provider = settings.fallback_provider
    if not backup_provider or backup_provider == settings.llm_provider:
        return main

    backup_model = settings.fallback_model or DEFAULT_MODELS.get(backup_provider, "")
    backup = _build_one(backup_provider, backup_model, settings)
    if backup is None:
        log.warning("LLM_FALLBACK=%s is set but its API key is missing: no fallback is active.", backup_provider)
        return main
    if main is None:
        return backup
    return FallbackLLM(main, backup, f"{settings.llm_provider}:{settings.llm_model}", f"{backup_provider}:{backup_model}")