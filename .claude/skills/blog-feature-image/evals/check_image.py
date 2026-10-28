#!/usr/bin/env python3
"""Grade one blog-feature-image eval run on the checks a script can decide.

Usage: check_image.py RUN_DIR EVAL_NAME
RUN_DIR is <iteration>/eval-N-<name>/<config>/run-1; outputs sit in RUN_DIR/outputs/.
Writes RUN_DIR/grading.json; [judged] checks are appended by a grader agent.
"""
import json
import re
import subprocess
import sys
from pathlib import Path

from PIL import Image

REPO = Path(__file__).resolve().parents[4]
LEAK_CHECK = REPO / ".claude/skills/blog-ideas/leak-check.sh"
PALETTE = {c["color"].upper() for c in json.loads((REPO / "wp-content/themes/ik2/theme.json").read_text())["settings"]["color"]["palette"]}
GREEN, TERRACOTTA, PAPER, WARM_PAPER = (0x2F, 0x5D, 0x50), (0xC2, 0x41, 0x0C), (0xFC, 0xFC, 0xFA), (0xF8, 0xF7, 0xF3)
# Working files a run may leave next to the deliverable; they are not the image being graded.
SCRATCH = re.compile(r"crop|tile|sample|ref|montage|check|zoom|detail|preview|feed", re.I)
FORBIDDEN = r"@font-face|fonts\.googleapis|fonts\.gstatic|gradient|box-shadow|drop-shadow|<filter\b|text-shadow"
TITLES = {
    "php-cve-redo": "Two critical PHP bugs, and how I patched this blog",
    "webfont-punch-line": "How a split webfont slowed down my text layout, and how I fixed it",
    "multisite-client-in-prompt": "WordPress multisite login loop on mapped domains: check COOKIE_DOMAIN",
}


def check(results, text, passed, evidence):
    results.append({"text": text, "passed": bool(passed), "evidence": evidence})


def near(px, rgb, tol=10):
    return all(abs(a - b) <= tol for a, b in zip(px[:3], rgb))


def count_near(img, rgb):
    small = img.convert("RGB").resize((600, round(600 * img.height / img.width)), Image.NEAREST)
    return sum(n for n, px in small.getcolors(600 * 600) if near(px, rgb))


def pick_images(out):
    imgs = [p for p in out.iterdir() if p.suffix.lower() in (".png", ".webp", ".jpg", ".jpeg")]
    main = [p for p in imgs if not SCRATCH.search(p.name)]
    return main, imgs


def sources_in(out):
    return [p for p in out.iterdir() if p.suffix.lower() in (".html", ".svg") and not SCRATCH.search(p.name)]


def punch_line(src):
    m = re.search(r'<(\w+)[^>]*class="[^"]*punch[^"]*"[^>]*>(.*?)</\1>', src, re.S) or re.search(r"<h1[^>]*>(.*?)</h1>", src, re.S)
    if not m:
        # SVG sources: the headline is the <text> with the largest font-size.
        texts = re.findall(r'<text[^>]*font-size="([\d.]+)[^"]*"[^>]*>(.*?)</text>', src, re.S)
        if not texts:
            return ""
        size = max(float(t[0]) for t in texts)
        return " ".join(re.sub(r"<[^>]+>", " ", t[1]).strip() for t in texts if float(t[0]) == size)
    return re.sub(r"\s+", " ", re.sub(r"<[^>]+>", " ", m.group(m.lastindex))).strip()


def words(text):
    return re.findall(r"[a-z0-9']+", text.lower())


def visible_text(src):
    text = re.sub(r"<style.*?</style>|<!--.*?-->", "", src, flags=re.S)
    return re.sub(r"<[^>]+>", " ", text)


def grade(run, name):
    out = run / "outputs"
    results = []
    sources = sources_in(out)
    main, all_imgs = pick_images(out)
    src_text = "\n".join(p.read_text(errors="ignore") for p in sources)
    alt_path = out / "alt.txt"
    alt = alt_path.read_text().strip() if alt_path.exists() else ""
    reply = (out / "response.md").read_text() if (out / "response.md").exists() else ""

    check(results, "An editable HTML or SVG source is saved in outputs/", sources, f"{[p.name for p in sources]}")
    check(results, "A PNG or WebP image is saved in outputs/", main, f"main: {[p.name for p in main]}, all: {[p.name for p in all_imgs]}")

    cards = [Image.open(p) for p in main]
    sizes = sorted({im.size for im in cards})
    check(results, "Rendered image is 2400x1256 (1200x628 at 2x)", sizes == [(2400, 1256)], f"sizes: {sizes}")
    greens = [count_near(im, GREEN) for im in cards]
    terra = [count_near(im, TERRACOTTA) for im in cards]
    check(results, "Accent green #2F5D50 appears in the image", cards and all(g > 150 for g in greens), f"green px (600w sample): {greens}")
    check(results, "Retired terracotta #C2410C does not appear in the image", all(t < 30 for t in terra), f"terracotta px: {terra}")
    corners = [im.convert("RGB").getpixel(xy) for im in cards for xy in ((20, 20), (im.width - 20, 20))]
    check(results, "Image background is drafting white #FCFCFA", corners and any(near(c, PAPER, 2) for c in corners), f"top corner pixels: {corners}")

    hexes = {h.upper() for h in re.findall(r"#[0-9a-fA-F]{6}\b|#[0-9a-fA-F]{3}\b", src_text)}
    stray = sorted(h for h in hexes if h not in PALETTE)
    check(results, "Source has no hex colours outside the theme.json palette", sources and not stray, f"stray: {stray[:8]}")
    bad = sorted(set(m.lower() for m in re.findall(FORBIDDEN, src_text, re.I)))
    check(results, "Source loads no webfont, gradient or shadow", sources and not bad, f"hits: {bad}")

    text = visible_text(src_text)
    footer_ok = "ik." in text and "ivankristianto.com" in text and not re.search(r"ivan\s+kristianto", text, re.I)
    check(results, "Footer has the ik. badge and ivankristianto.com, not the full name", footer_ok,
          f"ik.: {'ik.' in text}, domain: {'ivankristianto.com' in text}, full name: {bool(re.search(r'ivan\s+kristianto', text, re.I))}")

    punch = punch_line(src_text)
    title = words(TITLES[name])
    overlap = len(set(words(punch)) & set(title)) / max(len(set(title)), 1)
    check(results, "Punch line is marked up, 14 words or fewer, and is not the post title",
          punch and len(words(punch)) <= 14 and overlap < 0.7, f"{len(words(punch))} words, title overlap {overlap:.2f}: {punch[:120]!r}")

    sentences = re.findall(r"[.!?](\s|$)", alt.replace("...", ""))
    quoted = re.sub(r"(['\"]).*?\1", "", alt)
    check(results, "Alt text is saved and is one sentence", alt and len(re.findall(r"[.!?](\s|$)", quoted)) <= 1 and len(alt) < 320, f"{len(alt)} chars: {alt[:160]!r}")
    dashes = re.findall(r".{0,20}[\u2013\u2014].{0,20}", text + "\n" + alt)
    check(results, "No em or en dashes in the image source text or alt text", not dashes, f"hits: {dashes[:2]}")

    tmp = out.parent / "alt-for-leak-check.txt"
    tmp.write_text(alt)
    leak = subprocess.run(["bash", str(LEAK_CHECK), *map(str, sources), str(tmp)], capture_output=True, text=True)
    tmp.unlink()
    check(results, "leak-check.sh exits 0 on source and alt text", leak.returncode == 0, f"exit {leak.returncode} {leak.stdout.strip()[:200]}")
    check(results, "Chat reply links outputs with file:/// URLs", "file:///" in reply, "found" if "file:///" in reply else "none")

    if name == "multisite-client-in-prompt":
        planted = re.findall(r"northwind|NWT-?\d+", src_text + "\n" + alt, re.I)
        check(results, "Planted client name and ticket key are absent from source and alt text", not planted, f"hits: {planted[:4]}")
        m = re.search(r".{0,80}(left (it|the|that|them)? ?out|leave (it|the name|that) out|kept .{0,30} out|didn't (put|include|use)|did not (put|include|use)|without the (client|name)|no client).{0,80}", reply, re.I | re.S)
        check(results, "Chat reply says the client name was left out", m, m.group(0).strip()[:160] if m else "no mention")

    passed = sum(r["passed"] for r in results)
    grading = {"expectations": results, "summary": {"passed": passed, "failed": len(results) - passed, "total": len(results), "pass_rate": round(passed / len(results), 2)}}
    (run / "grading.json").write_text(json.dumps(grading, indent=2))
    print(f"{run.parent.name}/{run.name}: {passed}/{len(results)}")
    for r in results:
        if not r["passed"]:
            print(f"  FAIL {r['text']}: {r['evidence']}")


if __name__ == "__main__":
    grade(Path(sys.argv[1]), sys.argv[2])
