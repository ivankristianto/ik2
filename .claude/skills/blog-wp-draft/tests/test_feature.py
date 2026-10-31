"""Run: python3 -I -m unittest discover -s .claude/skills/blog-wp-draft/tests"""
import sys
import tempfile
import unittest
from pathlib import Path
from unittest import mock

HERE = Path(__file__).resolve().parent
sys.path.insert(0, str(HERE.parent / "scripts"))
import wp_draft  # noqa: E402

CARD = b"RIFF fake webp bytes"


class FakeWordPress:
    """Mimics REST + Yoast: a post save rebuilds og:image from the featured image as it was before that save."""

    def __init__(self, featured=0, og=None, media=None):
        self.post = {"id": 7, "status": "publish", "link": "https://example.test/p/", "featured_media": featured}
        self.og = og
        self.media = media or {}
        self.post_saves = 0

    def url(self, media_id):
        return self.media[media_id]["source_url"] if media_id else None

    def __call__(self, method, path, data=None, raw=None, headers=None):
        path = path.split("?")[0]
        if path == "/wp/v2/users/me":
            return {"id": 1, "capabilities": {"edit_posts": True}}
        if method == "GET" and path == "/wp/v2/media":
            return []
        if method == "POST" and path == "/wp/v2/media":
            self.media[50] = {"id": 50, "alt_text": "", "post": None,
                              "source_url": "https://example.test/card.webp", "media_details": {"filesize": len(raw)}}
            return self.media[50]
        if path.startswith("/wp/v2/media/"):
            item = self.media[int(path.rsplit("/", 1)[1])]
            if method == "POST":
                item.update(data)
            return dict(item)
        if path == "/wp/v2/posts/7":
            if method == "POST":
                self.post_saves += 1
                self.og = self.url(self.post["featured_media"])
                self.post.update(data)
            return {**self.post, "yoast_head_json": {"og_image": [{"url": self.og}] if self.og else []}}
        raise AssertionError(f"unexpected {method} {path}")


class Feature(unittest.TestCase):
    def setUp(self):
        tmp = tempfile.TemporaryDirectory()
        self.addCleanup(tmp.cleanup)
        self.assets = Path(tmp.name) / "some-post"
        self.assets.mkdir()
        (self.assets / "feature.webp").write_bytes(CARD)
        (self.assets / "alt.txt").write_text("A card.\n")

    def run_feature(self, wp, apply):
        with mock.patch.object(wp_draft, "call", wp):
            return wp_draft.feature(7, self.assets, apply)

    def test_second_save_fixes_stale_og_image(self):
        wp = FakeWordPress()
        report = self.run_feature(wp, apply=True)
        self.assertTrue(report["resaved"])
        self.assertEqual(wp.post_saves, 2)
        self.assertTrue(report["ok"], report["checks"])
        self.assertEqual(wp.media[50]["post"], 7)

    def test_rerun_with_same_card_writes_nothing(self):
        card = {"id": 50, "alt_text": "A card.", "post": 7, "source_url": "https://example.test/card.webp",
                "media_details": {"filesize": len(CARD)}}
        wp = FakeWordPress(featured=50, og=card["source_url"], media={50: card})
        report = self.run_feature(wp, apply=True)
        self.assertEqual((report["changes"], report["resaved"], wp.post_saves), ([], False, 0))
        self.assertTrue(report["ok"])

    def test_dry_run_flags_stale_og_image_without_writing(self):
        card = {"id": 50, "alt_text": "A card.", "post": 7, "source_url": "https://example.test/card.webp",
                "media_details": {"filesize": len(CARD)}}
        wp = FakeWordPress(featured=50, og="https://example.test/diagram.png", media={50: card})
        report = self.run_feature(wp, apply=False)
        self.assertEqual(wp.post_saves, 0)
        self.assertIn("re-save so Yoast's og:image picks up the featured image", report["changes"])

    def test_refuses_card_without_alt_text(self):
        (self.assets / "alt.txt").unlink()
        with self.assertRaises(wp_draft.Fail):
            self.run_feature(FakeWordPress(), apply=False)


if __name__ == "__main__":
    unittest.main()
