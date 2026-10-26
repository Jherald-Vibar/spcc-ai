import unittest

from app.chunker import chunk_segments, format_locator, split_long
from app.extractors import Segment


def words(n, prefix="w"):
    return " ".join(f"{prefix}{i}." if i % 10 == 9 else f"{prefix}{i}" for i in range(n))


class ChunkerTests(unittest.TestCase):
    def test_long_segment_is_split_with_overlap(self):
        pieces = split_long(words(700), target=300, overlap=40)
        self.assertGreaterEqual(len(pieces), 3)
        for p in pieces:
            self.assertLessEqual(len(p.split()), 300 + 40)
        # overlap: the end of piece 0 reappears at the start of piece 1
        tail = pieces[0].split()[-5:]
        self.assertTrue(all(t in pieces[1].split() for t in tail))

    def test_text_without_punctuation_is_hard_split(self):
        pieces = split_long(" ".join(f"x{i}" for i in range(1000)), 300, 40)
        self.assertTrue(all(len(p.split()) <= 340 for p in pieces))

    def test_tiny_slides_are_merged_until_min_words(self):
        segs = [Segment("slide", i, "", words(20, f"s{i}_")) for i in range(1, 8)]
        chunks = chunk_segments(segs, target=300, overlap=40, min_words=60)
        self.assertEqual(chunks[0].start, 1)
        self.assertEqual(chunks[0].end, 3)  # 20+20+20 reaches 60 -> next slide starts a new chunk
        self.assertEqual(sum(len(c.text.split()) for c in chunks), 140)

    def test_big_slides_stay_separate_so_citations_are_exact(self):
        segs = [Segment("slide", i, f"T{i}", words(100, f"s{i}_")) for i in range(1, 4)]
        chunks = chunk_segments(segs)
        self.assertEqual([(c.start, c.end) for c in chunks], [(1, 1), (2, 2), (3, 3)])

    def test_empty_segments_ignored(self):
        self.assertEqual(chunk_segments([Segment("page", 1, "", "   ")]), [])

    def test_locator_text(self):
        self.assertEqual(format_locator("slide", 5, 5), "Slide 5")
        self.assertEqual(format_locator("page", 3, 4), "Pages 3\u20134")
        self.assertEqual(format_locator("section", 2, 2, "Intro"), 'Section "Intro"')
        self.assertEqual(format_locator("section", 2, 3, "Intro"), "Sections 2\u20133")
        self.assertEqual(format_locator("image", 1, 1), "Image")


if __name__ == "__main__":
    unittest.main()
