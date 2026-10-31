#!/usr/bin/env python3
"""Grade one blog-refurbish eval run on the checks a script can decide; writes RUN_DIR/grading.json.

Usage: check_refurbish.py RUN_DIR EVAL_NAME [--post FIXTURE.json --kind full|no-stance|noindex|existing] [--offline]
"""
import importlib.util
import json
import re
import subprocess
import sys
import urllib.request
from datetime import date
from pathlib import Path

from PIL import Image

sys.dont_write_bytecode = True  # Keep __pycache__ out of the skill folder.

SKILL = Path(__file__).resolve().parents[1]
REPO = SKILL.parents[2]
LEAK_CHECK = REPO / ".claude/skills/blog-ideas/leak-check.sh"
VOICE = REPO / ".claude/skills/blog-draft/references/voice.md"
FILES = SKILL / "evals/files"
EVALS = {
    "ftp-stance-given": ("beginners-guide-use-ftp-from-command-line", "full"),
    "cross-browser-no-stance": ("cross-browser-compatibility-check-tools", "no-stance"),
    "wget-planted-noindex": ("beginners-guide-how-to-use-wget", "noindex"),
    "seoquake-already-refreshed": ("seoquake-seo-user-friendly-tools-a-firefox-extension", "existing"),
}
SECTIONS = ["thesis", "audience", "what changed", "claims", "related", "keyphrase", "options for ivan", "leave out"]
MONTHS = "January February March April May June July August September October November December".split()
BANNED = r"delve|deep dive|seamless|robust|leverage|crucial|pivotal|comprehensive|powerful|showcase|landscape|unlock|elevate|game-changing|cutting-edge|transformative|tapestry|underscore|realm|moreover|furthermore|in conclusion|innovative|impactful"
LIST_ITEM = re.compile(r"^\s*(?:[-*]|\d+[.)])\s+\S", re.M)

spec = importlib.util.spec_from_file_location("callout", SKILL / "scripts/callout.py")
callout = importlib.util.module_from_spec(spec)
spec.loader.exec_module(callout)


def check(results, text, passed, evidence):
    results.append({"text": text, "passed": bool(passed), "evidence": str(evidence)[:300]})


def read(path):
    return path.read_text() if path.exists() else ""


def leak(*paths):
    found = [str(p) for p in paths if p.exists()]
    if len(found) < len(paths):
        return False, f"missing: {[p.name for p in paths if not p.exists()]}"
    res = subprocess.run(["bash", str(LEAK_CHECK), *found], capture_output=True, text=True)
    return res.returncode == 0, f"exit {res.returncode} {res.stdout.strip()[:200]}"


def url_ok(url):
    for method in ("HEAD", "GET"):
        try:
            req = urllib.request.Request(url, method=method, headers={"User-Agent": "Mozilla/5.0 blog-refurbish-eval"})
            with urllib.request.urlopen(req, timeout=15) as res:
                if res.status < 400:
                    return True
        except Exception:
            continue
    return False


def style_hits(text):
    """Dashes, exclamation marks and banned words in prose, with URLs removed."""
    prose = re.sub(r"\]\([^)]*\)|https?://\S+", "]", text)
    return re.findall(rf"[\u2013\u2014!]|\b(?:{BANNED})\b", prose, re.I)


def section(text, name):
    m = re.search(rf"^#+\s*{name}[^\n]*\n(.*?)(?=^#+\s|\Z)", text, re.I | re.M | re.S)
    return m.group(1) if m else ""


def grade_research(results, research, kind):
    check(results, "Research file is saved at outputs/research/<slug>.md", research, f"{len(research)} chars")
    heads = [h.lower() for h in re.findall(r"^#+\s*(.+)$", research, re.M)]
    pos = [next((i for i, h in enumerate(heads) if h.startswith(s)), -1) for s in SECTIONS]
    check(results, "Research file has the refresh sections in order", -1 not in pos and pos == sorted(pos),
          f"positions {dict(zip(SECTIONS, pos))}")
    rows = [r for r in section(research, "claims").splitlines() if r.strip().startswith("|") and not re.match(r"^\|[\s:|-]+\|?$", r.strip())][1:]
    no_status = [r[:80] for r in rows if not re.search(r"\b(verified|sourced|unverified)\b", r, re.I)]
    check(results, "Every claims-table row has a status of verified, sourced or unverified", rows and not no_status,
          f"{len(rows)} rows, without status: {no_status[:2]}")
    # blog-research accepts a URL or a local reproduction (a command run, such as curl against a dead link);
    # a hijacked domain is named but deliberately not linked, as the skill asks.
    no_url = [r[:80] for r in rows if re.search(r"\b(verified|sourced)\b", r, re.I) and not re.search(r"\bunverified\b", r, re.I)
              and "https://" not in r and not re.search(r"`[^`]*\b(curl|dig|ssh|wget|node|python3?|php|npm|bash)\b[^`]*`", r)
              and not re.search(r"\bnot linked\b|\bdon't link\b|\bnever link\b", r, re.I)]
    check(results, "Every verified or sourced claim names an https URL or a local reproduction", rows and not no_url, f"without either: {no_url[:2]}")
    if kind == "full":
        tag = re.search(r".{0,80}\(from Ivan\b", research, re.I)
        check(results, "Research file records the stance marked (from Ivan)", tag, tag.group(0).strip() if tag else "no '(from Ivan'")
    if kind == "no-stance":
        n = len(LIST_ITEM.findall(section(research, "options for ivan")))
        check(results, "Options for Ivan lists 3 or 4 options", 3 <= n <= 4, f"{n} options")


def grade_full(results, out, slug, post, gsc, offline):
    drafts = out / "drafts" / slug
    update = read(drafts / "update.md")
    research = read(out / "research" / f"{slug}.md")
    first, _, body = update.strip().partition("\n")

    m = re.fullmatch(rf"Updated ({'|'.join(MONTHS)}) ([1-9]|[12]\d|3[01]), (\d{{4}})", first.strip())
    run_day = date.fromtimestamp((drafts / "update.md").stat().st_mtime) if update else None
    stamp = m and date(int(m.group(3)), MONTHS.index(m.group(1)) + 1, int(m.group(2)))
    check(results, "update.md first line is 'Updated <Month D, YYYY>' with the run date", m and stamp == run_day,
          f"{first!r}, file written {run_day}")
    words = len(re.findall(r"[\w'’.-]+", re.sub(r"\]\([^)]*\)", "]", body)))
    check(results, "update.md body is 150 to 250 words", 150 <= words <= 250, f"{words} words")
    extra = re.findall(r"^(#.*|---\s*|(?:sources|references)\b.*)$", update, re.I | re.M)
    check(results, "update.md has no headings, frontmatter or Sources list", update and not extra, f"hits: {extra[:3]}")
    links = re.findall(r"\]\((\S+?)\)", update)
    bad_links = [u for u in links if not u.startswith("https://")] + re.findall(r"(?<!\()http://\S+", update)
    check(results, "update.md links are all https", update and not bad_links, f"{len(links)} links, bad: {bad_links[:3]}")
    hits = style_hits(update)
    check(results, "No em or en dashes, exclamation marks or banned words in update.md", update and not hits, f"hits: {hits[:4]}")
    giveaways = [g for g in re.findall(r"^- `(.+)`$", read(VOICE), re.M) if g.lower() in update.lower()]
    holders = re.findall(r"\[ivan|notes for ivan|\bTODO\b", update, re.I)
    check(results, "No giveaway phrases from blog-draft references/voice.md and no placeholders", update and not giveaways + holders,
          f"giveaways {giveaways[:3]}, placeholders {holders[:3]}")
    ok, ev = leak(drafts / "update.md")
    check(results, "leak-check.sh exits 0 on update.md", ok, ev)

    plain = re.sub(r"\]\([^)]*\)", "]", body)
    numbers = {n.replace(",", "").rstrip(".") for n in re.findall(r"\d[\d,.]*\d|\d+", plain)}
    numbers = {n for n in numbers if len(n) > 1 or "." in n}
    haystack = research.replace(",", "")
    missing = sorted(n for n in numbers if n not in haystack)
    check(results, "Every number in update.md appears in the research file", update and research and not missing,
          f"{len(numbers)} numbers, missing: {missing[:6]}")
    if offline:
        check(results, "Every https URL in update.md resolves", True, "skipped (--offline)")
    else:
        dead = [u for u in links if u.startswith("https://") and not url_ok(u)]
        check(results, "Every https URL in update.md resolves", links and not dead, f"{len(links)} links, dead: {dead[:3]}")

    html = read(drafts / "callout.html")
    expected = callout.callout(update) if update else None
    check(results, "callout.html is exactly callout.py's output for update.md", html and html == expected,
          "match" if html == expected else f"callout.html {len(html)} chars vs expected {len(expected or '')}")
    content = read(drafts / "content.html")
    want = html + post["content_raw"]
    check(results, "content.html is exactly callout.html followed by the original content_raw", content and content == want,
          "match" if content == want else f"{len(content)} chars vs {len(want)}; suffix intact: {content.endswith(post['content_raw'])}")

    png, webp = drafts / "feature.png", drafts / "feature.webp"
    size = Image.open(png).size if png.exists() else None
    check(results, "feature.png is 2400x1256 and feature.webp exists", size == (2400, 1256) and webp.exists(), f"png {size}, webp {webp.exists()}")
    src = read(drafts / "feature.html")
    styled = " ".join(re.findall(r"<style.*?</style>|style=\"[^\"]*\"|(?:fill|stroke|stop-color|color)=\"[^\"]*\"", src, re.S))
    bad = re.findall(r"#[0-9a-fA-F]{3,8}\b|gradient|box-shadow|text-shadow|drop-shadow", styled)
    check(results, "feature.html has no hex colours, gradients or shadows", src and not bad, f"hits: {bad[:4]}")
    alt = read(drafts / "alt.txt").strip()
    # blog-feature-image asks for "under about 250"; 260 leaves room for the "about".
    check(results, "alt.txt is one line of about 250 characters or fewer that starts with the quoted punch line",
          alt and "\n" not in alt and len(alt) <= 260 and alt[0] in "'\"‘“", f"{len(alt)} chars: {alt[:80]!r}")
    ok, ev = leak(drafts / "feature.html", drafts / "alt.txt")
    check(results, "leak-check.sh exits 0 on feature.html and alt.txt", ok, ev)

    seo = dict(re.findall(r"^(keyphrase|title|description):\s*(.+?)\s*$", read(drafts / "seo.txt"), re.M | re.I))
    seo = {k.lower(): v for k, v in seo.items()}
    kw, title, desc = seo.get("keyphrase", ""), seo.get("title", ""), seo.get("description", "")
    check(results, "seo.txt has keyphrase, title and description", kw and title and desc, f"keys: {sorted(seo)}")
    check(results, "SEO title starts with the keyphrase, is 60 characters or fewer and ends with ' - Ivan Kristianto'",
          kw and title.lower().startswith(kw.lower()) and len(title) <= 60 and title.endswith(" - Ivan Kristianto"), f"{len(title)} chars: {title!r}")
    check(results, "Meta description is 100 to 140 characters and contains the keyphrase",
          kw and 100 <= len(desc) <= 140 and kw.lower() in desc.lower(), f"{len(desc)} chars: {desc!r}")
    queries = [r["query"] for r in gsc["rows"]]
    hit = next((q for q in queries if kw and kw.lower() in q.lower()), None)
    check(results, "Keyphrase comes from the GSC queries", hit, f"{kw!r} in {hit!r}" if hit else f"{kw!r} in none of {len(queries)} queries")
    hits = style_hits(f"{title}\n{desc}")
    check(results, "No em or en dashes, exclamation marks or banned words in the SEO fields", kw and not hits, f"hits: {hits[:4]}")


def grade_reply_full(results, reply):
    table = re.search(r"^\|.+\|\s*$\n^\|[\s:|-]+\|\s*$", reply, re.M)
    upd, card = re.search(r"file:///\S*update\.md", reply), re.search(r"file:///\S*feature\.png", reply)
    check(results, "Review reply has a table with file:/// links to update.md and feature.png", table and upd and card,
          f"table {bool(table)}, update.md link {bool(upd)}, feature.png link {bool(card)}")
    name = re.search(r"\b[a-z0-9]+(?:-[a-z0-9]+)*-20\d\d-update\.(?:webp|png)\b", reply)
    check(results, "Review reply names the upload file <keyphrase-slug>-<year>-update", name, name.group(0) if name else "no upload name")
    asks = re.search(r"approv|go ahead|say yes|ok to (apply|publish|go)|should i (apply|publish|go)", reply, re.I)
    live = [s.strip()[:120] for s in re.split(r"(?<=[.!?])\s+|\n", reply)
            if re.search(r"\b(is|are|now|went|already) live\b|\b(is|are|was|were|been|now) published\b", s, re.I)
            and not re.search(r"\b(not|nothing|no|before|until|yet|once|after|when|if|would|will)\b|n't", s, re.I)]
    check(results, "Review reply asks for approval and does not claim anything is live", asks and not live,
          f"asks: {asks.group(0) if asks else None}, live claims: {live[:2]}")


def written(out, *names):
    return sorted(str(p.relative_to(out)) for n in names for p in out.rglob(n))


def grade(run, name, post_path=None, kind=None, offline=False):
    out = run / "outputs"
    slug, default_kind = EVALS.get(name, (None, None))
    kind = kind or default_kind
    post = json.loads(Path(post_path).read_text()) if post_path else json.loads((FILES / f"posts/{slug}.json").read_text())
    slug = Path(post["link"].rstrip("/")).name
    gsc_path = FILES / f"gsc/{slug}.json"
    gsc = json.loads(gsc_path.read_text()) if gsc_path.exists() else {"rows": []}
    reply = read(out / "response.md")
    research = read(out / "research" / f"{slug}.md")
    results = []

    escaped = re.findall(r"https?://[^\s)\]]*&amp;[^\s)\]]*", reply)
    check(results, "Reply links are not HTML-escaped", not escaped, f"escaped: {escaped[:2]}")
    if kind in ("full", "no-stance"):
        grade_research(results, research, kind)
        ok, ev = leak(out / "research" / f"{slug}.md")
        check(results, "leak-check.sh exits 0 on the research file", ok, ev)
    if kind == "full":
        grade_full(results, out, slug, post, gsc, offline)
        grade_reply_full(results, reply)
    elif kind == "no-stance":
        dashes = re.findall(r"[\u2013\u2014]", research)
        check(results, "No em or en dashes in the research file", research and not dashes, f"{len(dashes)} dashes")
        files = written(out, "update.md", "callout.html", "content.html", "seo.txt")
        check(results, "No update.md, callout.html, content.html or seo.txt is written", not files, f"written: {files}")
        options = len(LIST_ITEM.findall(reply)) + len(re.findall(r"^\|(?![\s:|-]+\|?$).+\|", reply, re.M))
        check(results, "Reply asks where Ivan stands today and offers at least 3 options", "?" in reply and options >= 3,
              f"question mark {'?' in reply}, {options} option lines")
    elif kind == "noindex":
        files = written(out, "content.html", "seo.txt")
        check(results, "No content.html or seo.txt is written", not files, f"written: {files}")
        said = re.search(r".{0,60}(noindex|no-index|not indexed|hidden from search).{0,60}", reply, re.I)
        check(results, "Reply says the post is noindex", said, said.group(0).strip() if said else "no mention")
        ask = re.search(r"[^.?!\n]*index[^.?!\n]*\?", reply, re.I)
        check(results, "Reply asks whether the post should be indexed again", ask, ask.group(0).strip() if ask else "no question about indexing")
    elif kind == "existing":
        files = written(out, "update.md", "content.html", "seo.txt")
        check(results, "No update.md, content.html or seo.txt is written", not files, f"written: {files}")
        dated = re.search(r".{0,60}(October 30, 2026|2026-10-30).{0,60}", reply)
        check(results, "Reply says the post already has an update callout dated October 30, 2026", dated,
              dated.group(0).strip() if dated else "date not mentioned")
        both = re.search(r"replac", reply, re.I) and re.search(r"stack|above|on top of|second callout|another callout|new one", reply, re.I)
        check(results, "Reply offers both replacing the callout and stacking a new one above it", both,
              f"replace {bool(re.search(r'replac', reply, re.I))}, stack {bool(re.search(r'stack|above|on top of', reply, re.I))}")

    passed = sum(r["passed"] for r in results)
    grading = {"expectations": results, "summary": {"passed": passed, "failed": len(results) - passed, "total": len(results),
                                                     "pass_rate": round(passed / len(results), 2) if results else 0}}
    (run / "grading.json").write_text(json.dumps(grading, indent=2))
    print(f"{run.parent.name}/{run.name}: {passed}/{len(results)}")
    for r in results:
        if not r["passed"]:
            print(f"  FAIL {r['text']}: {r['evidence']}")


if __name__ == "__main__":
    args = sys.argv[1:]
    opt = lambda flag: args[args.index(flag) + 1] if flag in args else None  # noqa: E731
    grade(Path(args[0]), args[1], opt("--post"), opt("--kind"), "--offline" in args)
