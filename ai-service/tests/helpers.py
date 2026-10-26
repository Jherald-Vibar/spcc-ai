import hashlib
import re

import numpy as np


STOP = {"the","and","for","who","was","are","how","what","does","each","that","with","this","from","uses","use"}


class FakeEmbedder:
    """Bag-of-words hashing embedder: similarity is driven by shared words. Test use only."""
    name = "fake"
    DIM = 256

    def _vec(self, text):
        v = np.zeros(self.DIM)
        for w in re.findall(r"[a-zA-Z]+", text.lower()):
            if len(w) < 3 or w in STOP:
                continue
            v[int(hashlib.md5(w.encode()).hexdigest(), 16) % self.DIM] += 1
        n = np.linalg.norm(v)
        return (v / n if n else v).tolist()

    def embed_documents(self, texts):
        return [self._vec(t) for t in texts]

    def embed_query(self, text):
        return self._vec(text)


class FakeLLM:
    def __init__(self, reply):
        self.reply = reply
        self.calls = []

    def complete(self, system, user, max_tokens=800):
        self.calls.append((system, user))
        return self.reply
