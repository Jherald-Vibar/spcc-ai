import json as _json
from app.store import Hit, MemoryStore
from app.video_search import VideoSearchError, VideoSearchService, extract_video_ids

GOOD, GONE, FAKE = "dQw4w9WgXcQ", "aaaaaaaaaaa", "bbbbbbbbbbb"


class Resp:
    def __init__(self, status, payload=None):
        self.status_code, self._p = status, payload or {}

    def json(self):
        return self._p

    def raise_for_status(self):
        if self.status_code >= 400:
            raise RuntimeError(f"HTTP {self.status_code}")


class FakeClient:
    """Stands in for httpx.Client: routes Gemini POSTs and oEmbed GETs to the test handler."""

    def __init__(self, handler):
        self.handler = handler

    def post(self, url, json=None, headers=None):
        return self.handler("POST", url, json, {})

    def get(self, url, params=None):
        return self.handler("GET", url, None, params or {})


def make(handler, key="k"):
    store = MemoryStore()
    store.replace_file(1, ["1-0"], ["Vigenere cipher uses a keyword."], [[1.0]], [{"file_id": 1, "lesson_id": 7, "chunk_index": 0}])
    return VideoSearchService(store, key, "gemini-2.5-flash", client=FakeClient(handler))


def handler(method, url, body, params):
    if "generativelanguage" in url:
        assert body["tools"] == [{"google_search": {}}]
        text = (f"VIDEO | Good | https://www.youtube.com/watch?v={GOOD}\n"
                f"VIDEO | Removed | https://youtu.be/{GONE}\n"
                f"VIDEO | Invented | https://www.youtube.com/watch?v={FAKE}\n"
                f"VIDEO | Dup | https://www.youtube.com/watch?v={GOOD}&t=3")
        return Resp(200, {"candidates": [{"content": {"parts": [{"text": text}]}}]})
    vid = params["url"].split("v=")[1]
    if vid == GOOD:
        return Resp(200, {"title": "Vigenere explained", "author_name": "CS Channel"})
    return Resp(404)


def test_only_verified_videos_are_returned():
    out = make(handler).suggest([7], "Polyalphabetic Ciphers")
    assert [v["video_id"] for v in out] == [GOOD]
    assert out[0]["title"] == "Vigenere explained" and out[0]["channel"] == "CS Channel"
    assert out[0]["thumbnail"].startswith("https://i.ytimg.com/")


def test_extract_ids():
    assert extract_video_ids("https://m.youtube.com/shorts/dQw4w9WgXcQ", "see youtu.be/aaaaaaaaaaa?t=4") == [GOOD, GONE]
    assert extract_video_ids("https://evil.com/watch?v=dQw4w9WgXcQ", "javascript:alert(1)") == []


def test_gemini_failure_and_missing_key():
    try:
        make(lambda *a: Resp(503)).suggest([7], "x y")
        assert False
    except VideoSearchError:
        pass
    try:
        make(handler, key="").suggest([7], "x y")
        assert False
    except VideoSearchError:
        pass
