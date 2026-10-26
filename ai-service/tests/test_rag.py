import unittest

from app.chunker import Chunk
from app.rag import NOT_FOUND_MESSAGE, RagService
from app.store import MemoryStore
from tests.helpers import FakeEmbedder, FakeLLM

DOCS = {
    # file_id, lesson_id, text
    (1, 10): ("The Caesar cipher is a substitution cipher that shifts each letter of the plaintext "
              "by a fixed number of positions in the alphabet. Caesar cipher is easy to break "
              "with brute force because there are only 25 possible keys."),
    (2, 10): ("A firewall filters network traffic using rules for source address, destination "
              "address, port and protocol. Firewall policies are default deny or default allow."),
    (3, 20): ("Photosynthesis converts light energy into chemical energy in plants."),
}


def build(reply, threshold=0.25):
    emb, store = FakeEmbedder(), MemoryStore()
    for (file_id, lesson_id), text in DOCS.items():
        store.replace_file(file_id, [f"{file_id}-0"], [text], emb.embed_documents([text]), [{
            "file_id": file_id, "lesson_id": lesson_id, "file_name": f"f{file_id}.pdf",
            "lesson_title": "Lesson", "week": 2, "unit": "page", "start": file_id, "end": file_id,
            "label": "",
        }])
    llm = FakeLLM(reply)
    return RagService(emb, store, llm, threshold, top_k=5), llm


class RagTests(unittest.TestCase):
    def test_off_topic_question_never_calls_the_llm(self):
        rag, llm = build("should not be used")
        ans = rag.ask("Who won the basketball finals in 2019?", [10, 20])
        self.assertFalse(ans.found)
        self.assertEqual(ans.answer, NOT_FOUND_MESSAGE)
        self.assertEqual(llm.calls, [])
        self.assertFalse(ans.llm_called)

    def test_relevant_question_returns_answer_with_only_cited_sources(self):
        rag, llm = build("The Caesar cipher shifts each letter by a fixed number [1].")
        ans = rag.ask("How does the Caesar cipher work?", [10])
        self.assertTrue(ans.found)
        self.assertEqual([s.file_id for s in ans.sources], [1])
        self.assertEqual(ans.sources[0].locator, "Page 1")
        self.assertIn("<excerpts>", llm.calls[0][1])
        self.assertIn("ONLY", llm.calls[0][0])

    def test_sentinel_from_llm_becomes_not_found_without_sources(self):
        rag, _ = build("NOT_IN_LECTURE")
        ans = rag.ask("What is the Caesar cipher key size?", [10])
        self.assertFalse(ans.found)
        self.assertEqual(ans.answer, NOT_FOUND_MESSAGE)
        self.assertEqual(ans.sources, [])
        self.assertTrue(ans.llm_called)

    def test_retrieval_is_limited_to_the_allowed_lessons(self):
        rag, llm = build("ok [1]")
        ans = rag.ask("How does photosynthesis convert light energy?", [10])  # lesson 20 not allowed
        self.assertFalse(ans.found)
        self.assertEqual(llm.calls, [])

    def test_empty_lesson_list_is_not_found(self):
        rag, _ = build("x")
        self.assertFalse(rag.ask("Caesar cipher?", []).found)

    def test_invalid_markers_are_removed_and_missing_citations_fall_back(self):
        rag, _ = build("Caesar cipher shifts letters [9].")
        ans = rag.ask("Explain the Caesar cipher substitution", [10])
        self.assertNotIn("[9]", ans.answer)
        self.assertTrue(ans.sources)  # fell back to best matches

    def test_missing_llm_raises(self):
        rag, _ = build("x")
        rag.llm = None
        with self.assertRaises(RuntimeError):
            rag.ask("Caesar cipher substitution brute force", [10])


if __name__ == "__main__":
    unittest.main()
