import json
import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "scripts"))
import publish  # noqa: E402
from providers import REGISTRY, youtube  # noqa: E402
from providers.common import Fail  # noqa: E402

FUNCTIONS = ("validate", "preflight", "plan", "upload", "readback")


def meta(**over):
    base = {
        "slug": "some-post",
        "url": "https://www.ivankristianto.com/some-post/",
        "orientation": "vertical",
        "video": "out/video.mp4",
        "cover": "cover.jpg",
        "title": "A short title",
        "description": "What it shows.\n\nFull article: https://www.ivankristianto.com/some-post/",
        "tags": ["wordpress", "php"],
        "privacy": "private",
        "syntheticMedia": False,
    }
    return {**base, **over}


class Registry(unittest.TestCase):
    def test_every_provider_has_the_interface(self):
        for name, mod in REGISTRY.items():
            self.assertEqual(mod.NAME, name)
            for fn in FUNCTIONS:
                self.assertTrue(callable(getattr(mod, fn, None)), f"{name}.{fn}")
            for env, ref in mod.CREDENTIALS.items():
                self.assertTrue(ref.startswith("op://Agents/"), f"{name} {env} must come from the Agents vault")

    def test_pick_defaults_to_every_provider(self):
        self.assertEqual([p.NAME for p in publish.pick(None)], list(REGISTRY))

    def test_pick_rejects_unknown(self):
        with self.assertRaises(Fail):
            publish.pick("youtube,myspace")


class YouTubeLimits(unittest.TestCase):
    def setUp(self):
        self.dir = tempfile.TemporaryDirectory()
        self.vdir = Path(self.dir.name)
        (self.vdir / "cover.jpg").write_bytes(b"\xff\xd8" + b"0" * 100)

    def tearDown(self):
        self.dir.cleanup()

    def test_clean_metadata_passes(self):
        self.assertEqual(youtube.validate(meta(), self.vdir), [])

    def test_limits(self):
        cases = {
            "title": meta(title="x" * 101),
            "description": meta(description="y" * 5001),
            "< or >": meta(title="use <dialog>"),
            "tags": meta(tags=["tag with spaces"] * 40),
            "privacy": meta(privacy="friends"),
            "syntheticMedia": meta(syntheticMedia=None),
        }
        for word, m in cases.items():
            problems = youtube.validate(m, self.vdir)
            self.assertTrue(any(word in p for p in problems), f"{word}: {problems}")

    def test_cover_over_2mb(self):
        (self.vdir / "cover.jpg").write_bytes(b"0" * (youtube.THUMB_MAX + 1))
        self.assertTrue(any("2 MB" in p for p in youtube.validate(meta(), self.vdir)))

    def test_tags_length_counts_quotes_and_commas(self):
        self.assertEqual(youtube.tags_length(["ab", "c d"]), 2 + 5 + 1)

    def test_body_never_targets_kids_and_carries_disclosure(self):
        body = youtube.video_body(meta(syntheticMedia=True))
        self.assertFalse(body["status"]["selfDeclaredMadeForKids"])
        self.assertTrue(body["status"]["containsSyntheticMedia"])
        self.assertEqual(body["snippet"]["categoryId"], "28")


class Receipts(unittest.TestCase):
    def setUp(self):
        self.dir = tempfile.TemporaryDirectory()
        self.vdir = Path(self.dir.name)
        (self.vdir / "cover.jpg").write_bytes(b"\xff\xd8")

    def tearDown(self):
        self.dir.cleanup()

    def write(self, receipt):
        (self.vdir / "out").mkdir(exist_ok=True)
        (self.vdir / "out" / "youtube-upload-receipt.json").write_text(json.dumps(receipt))

    def test_no_receipt_plans_an_upload(self):
        self.assertEqual(youtube.plan(meta(), self.vdir)["action"], "upload video, set thumbnail, read back")

    def test_full_receipt_skips(self):
        self.write({"id": "abc", "url": "u", "thumbnail": {"setAt": "now"}})
        self.assertTrue(youtube.plan(meta(), self.vdir)["action"].startswith("skip"))

    def test_older_receipt_shape_counts_as_done(self):
        self.write({"id": "abc", "url": "u", "thumbnailSetResponse": [{}]})
        self.assertTrue(youtube.plan(meta(), self.vdir)["action"].startswith("skip"))

    def test_receipt_without_thumbnail_finishes(self):
        self.write({"id": "abc", "url": "u"})
        self.assertTrue(youtube.plan(meta(), self.vdir)["action"].startswith("finish"))


class Common(unittest.TestCase):
    def test_upload_needs_apply(self):
        with self.assertRaises(Fail):
            publish.main(["upload", "/nowhere"])

    def test_description_must_carry_the_post_url(self):
        with tempfile.TemporaryDirectory() as d:
            problems, _ = publish.check_common(meta(description="no link"), d, online=False)
        self.assertTrue(any("post URL" in p for p in problems))


if __name__ == "__main__":
    unittest.main()
