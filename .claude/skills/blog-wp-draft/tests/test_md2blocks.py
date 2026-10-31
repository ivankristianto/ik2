"""Run: python3 -I -m unittest discover -s .claude/skills/blog-wp-draft/tests"""
import sys
import tempfile
import unittest
from pathlib import Path

HERE = Path(__file__).resolve().parent
sys.path.insert(0, str(HERE.parent / "scripts"))
from md2blocks import DraftError, convert, load_draft, page, read_seo  # noqa: E402

FIXTURES = HERE / "fixtures"


class Golden(unittest.TestCase):
    def test_sample_matches_the_checked_in_blocks(self):
        meta, blocks = load_draft(FIXTURES / "sample.md")
        self.assertEqual(blocks + "\n", (FIXTURES / "sample.blocks.html").read_text())

    def test_frontmatter(self):
        meta, _ = load_draft(FIXTURES / "sample.md")
        self.assertEqual(meta["title"], 'A sample post with "quotes"')
        self.assertEqual(meta["tags"], ["claude code", "macos"])


class Rejects(unittest.TestCase):
    def assertRejects(self, body, needle):
        with self.assertRaises(DraftError) as ctx:
            convert(body)
        self.assertIn(needle, str(ctx.exception))

    def test_bold(self):
        self.assertRejects("Some **bold** text.", "line 1: bold")

    def test_bold_inside_inline_code_is_fine(self):
        self.assertIn("<code>**</code>", convert("Glob `**` paths."))

    def test_table(self):
        self.assertRejects("Intro.\n\n| a | b |", "line 3")

    def test_image(self):
        self.assertRejects("![alt](https://example.com/a.png)", "images")

    def test_nested_list(self):
        self.assertRejects("- one\n  - two", "nested")

    def test_unknown_fence_language(self):
        self.assertRejects("```rust\nfn main() {}\n```", "rust")

    def test_unclosed_fence(self):
        self.assertRejects("```bash\nls", "never closed")

    def test_h1_in_body(self):
        self.assertRejects("# Title", "H1")

    def test_published_status(self):
        with tempfile.NamedTemporaryFile("w", suffix=".md", delete=False) as f:
            f.write("---\ntitle: t\nslug: s\nexcerpt: e\ncategory: ai\nstatus: publish\n---\n\nHi.\n")
        with self.assertRaises(DraftError):
            load_draft(f.name)


class Seo(unittest.TestCase):
    def test_both_seo_txt_shapes(self):
        with tempfile.TemporaryDirectory() as d:
            a, b = Path(d, "a.txt"), Path(d, "b.txt")
            a.write_text("Focus keyphrase: op shim\nSEO title (40): Op shim - Ivan Kristianto\nMeta description (130): I wrote it.\n")
            b.write_text("keyphrase: op shim\ntitle: Op shim - Ivan Kristianto\ndescription: I wrote it.\n")
            self.assertEqual(read_seo(a), read_seo(b))
            self.assertEqual(read_seo(a)["title"], "Op shim - Ivan Kristianto")

    def test_page_has_what_audit_reads(self):
        meta, blocks = load_draft(FIXTURES / "sample.md")
        html = page(meta, blocks, {"title": "T - Ivan Kristianto", "description": "D"}, "alt words")
        for needle in ("<main>", "ik-article__body", "ik-article__featured", 'alt="alt words"', "<h1>A sample post with &quot;quotes&quot;</h1>"):
            self.assertIn(needle, html)


if __name__ == "__main__":
    unittest.main()
