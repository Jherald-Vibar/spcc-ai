"""Split segments into chunks of roughly CHUNK_WORDS words without crossing page/slide boundaries
more than needed, so citations stay accurate."""
from __future__ import annotations

import re
from dataclasses import dataclass

from .extractors import Segment

_SENTENCE = re.compile(r"(?<=[.!?])\s+|\n+")


@dataclass
class Chunk:
    text: str
    unit: str
    start: int   # first page/slide/section number covered
    end: int     # last one (equal to start unless tiny neighbours were merged)
    label: str


def _words(text: str) -> int:
    return len(text.split())


def split_long(text: str, target: int, overlap: int) -> list[str]:
    """Split one long segment on sentence boundaries, with a small overlap between pieces."""
    units: list[str] = []
    for sentence in (s.strip() for s in _SENTENCE.split(text)):
        if not sentence:
            continue
        words = sentence.split()
        if len(words) <= target:
            units.append(sentence)
        else:  # no punctuation at all: hard split by words
            units.extend(" ".join(words[i:i + target]) for i in range(0, len(words), target))

    pieces: list[str] = []
    current: list[str] = []
    current_words = 0
    for unit in units:
        n = _words(unit)
        if current and current_words + n > target:
            pieces.append(" ".join(current))
            keep: list[str] = []
            kept = 0
            for previous in reversed(current):
                w = _words(previous)
                if kept + w > overlap:
                    break
                keep.insert(0, previous)
                kept += w
            current, current_words = keep, kept
        current.append(unit)
        current_words += n
    if current:
        pieces.append(" ".join(current))
    return pieces


def chunk_segments(
    segments: list[Segment], target: int = 300, overlap: int = 40, min_words: int = 60
) -> list[Chunk]:
    chunks: list[Chunk] = []
    buffer: list[Segment] = []
    buffer_words = 0

    def flush() -> None:
        nonlocal buffer, buffer_words
        if buffer:
            chunks.append(
                Chunk(
                    "\n".join(s.text for s in buffer),
                    buffer[0].unit,
                    buffer[0].number,
                    buffer[-1].number,
                    buffer[0].label,
                )
            )
        buffer, buffer_words = [], 0

    for seg in segments:
        n = _words(seg.text)
        if n == 0:
            continue
        if n > target:
            flush()
            for piece in split_long(seg.text, target, overlap):
                chunks.append(Chunk(piece, seg.unit, seg.number, seg.number, seg.label))
            continue
        # tiny neighbours (e.g. a title-only slide) are merged until the chunk has min_words
        if buffer and (buffer_words >= min_words or buffer_words + n > target):
            flush()
        buffer.append(seg)
        buffer_words += n
    flush()
    return chunks


def format_locator(unit: str, start: int, end: int, label: str = "") -> str:
    """Human-readable citation location, e.g. 'Slide 5', 'Pages 3-4', 'Section "Intro"'."""
    if unit == "image":
        return "Image"
    if unit == "section":
        if start == end and label:
            return f'Section "{label}"'
        return f"Section {start}" if start == end else f"Sections {start}\u2013{end}"
    word = unit.capitalize()
    return f"{word} {start}" if start == end else f"{word}s {start}\u2013{end}"
