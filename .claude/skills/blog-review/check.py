#!/usr/bin/env python3
"""The mechanical half of a blog review: what a script can decide on a post saved as Markdown.

Usage: python3 -I check.py POST.md [--json OUT] [--offline]
Gates (dashes, banned words, giveaway phrases, leaks, placeholders, links, footnotes) are final.
Signals (hypotheticals, faceless provenance, contrast formulas, rhythm) go to the judge to rule on.
"""
import json
import re
import statistics
import subprocess
import sys
import urllib.request
from pathlib import Path

REPO = Path(__file__).resolve().parents[3]
LEAK_CHECK = REPO / ".claude/skills/blog-ideas/leak-check.sh"
VOICE = REPO / ".claude/skills/blog-draft/references/voice.md"

# The vocabulary guardrails in ~/.claude/CLAUDE.md, plus what blog-draft's checker bans.
BANNED = (r"delve|deep dive|unlock|elevate|seamless|robust|cutting-edge|future-ready|transformative|leverag(?:e|es|ed|ing)"
          r"|tapestry|underscore|showcase|boasts?|intricate|meticulous|realm|landscape|ever-evolving|fast-paced world"
          r"|moreover|furthermore|crucial|pivotal|game-changing|powerful|comprehensive|innovative|impactful|in conclusion")
TEMPLATE_HEADINGS = (r"(the )?short answer|what i have(n't| not) (yet )?(verified|tested)|where this stops applying|introduction"
                     r"|overview|background|conclusion|summary|final thoughts|wrapping up|the fix|the problem|the solution|key takeaways?")
CONTRAST = [
    r"\bit'?s not (?:just |only |about )?[^.!?\n]{1,60}[,;.] it'?s\b",
    r"\bnot just [^.!?\n]{1,60}\bbut\b",
    r"\bnot because [^.!?\n]{1,60}\. but because\b",
    r"\bNo [^.!?\n]{1,30}\. No [^.!?\n]{1,30}\. Just\b",
    r"\bthe result\? ",
    r"\bthink of it as\b",
    r"\bimagine a world\b",
    r"\bwhether you(?:'re|’re| are) [^.!?\n]{1,40}, [^.!?\n]{1,40},? or\b",
    r"\blet'?s dive in\b",
]
HYPOTHETICAL = r"(?:^|[.?!]\s+)(Imagine|Suppose|Picture this|Let'?s say|Say you|Pretend)\b"
FACELESS = (r"\bthese notes\b|\bfor this (?:post|article)\b|\bwhile writing this\b"
            r"|\b(?:was|were|has been|have been|hasn['’]t been|haven['’]t been|had) (?:checked|tested|exercised|verified|confirmed|validated)\b")
FIRST_PERSON = r"\b(?:I|I'm|I’m|I've|I’ve|I'd|I’d|I'll|I’ll|my|me|mine)\b"
TIME_ANCHOR = (r"\b(?:19|20)\d\d-\d\d-\d\d\b|\b(?:January|February|March|April|May|June|July|August|September|October|November|December)"
               r" \d{1,2}\b|\b(?:yesterday|last (?:night|week|month)|this (?:morning|weekend|week)|that night|next morning|on (?:Mon|Tues|Wednes|Thurs|Fri|Satur|Sun)day)\b")
VERSION = r"\b(?:[A-Z][\w.+-]*|v) ?\d+\.\d+(?:\.\d+)?\b"


def split_body(text):
    """Return (body, sources) with code fences removed from neither; Sources starts at a Sources heading."""
    m = re.search(r"^#{2,4}\s*(?:Sources|References|Further reading)\s*$", text, re.M | re.I)
    return (text[:m.start()], text[m.start():]) if m else (text, "")


def prose(text):
    text = re.sub(r"^---\n.*?\n---\n", "", text, flags=re.S)
    text = re.sub(r"```.*?```", "", text, flags=re.S)
    return re.sub(r"`[^`\n]+`", "", text)


def sentences(text):
    lines = [l for l in text.splitlines() if l.strip() and not l.lstrip().startswith(("#", "|"))]
    flat = re.sub(r"\[([^\]]+)\]\([^)]+\)", r"\1", " ".join(lines))
    return [s for s in re.split(r"(?<=[.?!])\s+(?=[A-Z0-9\"“])", flat) if len(s.split()) >= 2]


def url_ok(url):
    for method in ("HEAD", "GET"):
        try:
            req = urllib.request.Request(url, method=method, headers={"User-Agent": "Mozilla/5.0 blog-review"})
            with urllib.request.urlopen(req, timeout=15) as res:
                if res.status < 400:
                    return True
        except Exception:
            continue
    return False


def footnotes(body, sources):
    cited = [int(n) for n in re.findall(r"\[(\d{1,2})\](?!\()", prose(body))]
    listed = [int(n) for n in re.findall(r"^\s*(?:[-*]|\d+\.)?\s*\[(\d{1,2})\]", sources, re.M)]
    if not cited and not listed:
        return None
    first_seen = list(dict.fromkeys(cited))
    problems = []
    if missing := sorted(set(cited) - set(listed)):
        problems.append(f"cited but not in Sources: {missing}")
    if unused := sorted(set(listed) - set(cited)):
        problems.append(f"in Sources but never cited: {unused}")
    return problems, first_seen == sorted(first_seen), first_seen


def main():
    args = sys.argv[1:]
    if not args:
        sys.exit(__doc__)
    path = Path(args[0])
    out = Path(args[args.index("--json") + 1]) if "--json" in args else None
    offline = "--offline" in args
    text = path.read_text()
    body, sources = split_body(text)
    body_prose = prose(body)
    checks = []

    def add(item, kind, text_, passed, evidence):
        checks.append({"id": item, "kind": kind, "text": text_, "passed": bool(passed), "evidence": evidence})

    dashes = [l.strip()[:120] for l in prose(text).splitlines() if re.search(r"[\u2013\u2014]", l)]
    add("W1", "gate", "No em or en dashes in prose", not dashes, dashes[:5] or "none")
    banned = sorted({m.lower() for m in re.findall(BANNED, body_prose, re.I)})
    add("W2", "gate", "No banned vocabulary", not banned, banned or "none")
    giveaways = re.findall(r"^- `(.+)`$", VOICE.read_text(), re.M) if VOICE.exists() else []
    normalized = body_prose.lower().replace("’", "'")
    found = sorted({g for g in giveaways if g.lower() in normalized})
    add("W3", "gate", "No giveaway phrases from blog-draft/references/voice.md", not found, found or "none")
    bangs = [l.strip()[:120] for l in body_prose.splitlines() if re.search(r"[A-Za-z)]!(\s|$)", l)]
    add("W3b", "signal", "No exclamation marks in prose", not bangs, bangs[:3] or "none")

    contrast = [m.group(0) for p in CONTRAST for m in re.finditer(p, body_prose, re.I)]
    add("W4", "signal", "No canned contrast formulas or slogan openers", not contrast, contrast[:5] or "none")
    hypo = [m.group(1) + body_prose[m.end(1):m.end(1) + 70].split("\n")[0] for m in re.finditer(HYPOTHETICAL, body_prose, re.M)]
    add("W5", "signal", "No hypothetical scaffolding (Imagine, Suppose, Let's say)", not hypo, hypo[:5] or "none")
    faceless = sorted({m.group(0) for m in re.finditer(FACELESS, body_prose, re.I)})
    add("W6", "signal", "No faceless provenance (these notes, was checked) that hides who did the work", not faceless, faceless or "none")

    sents = sentences(body_prose)
    lengths = [len(s.split()) for s in sents]
    cv = round(statistics.pstdev(lengths) / statistics.mean(lengths), 2) if len(lengths) > 3 else None
    openers = [s.split()[0].lower().strip('"“') for s in sents]
    runs = [openers[i] for i in range(len(openers) - 2) if openers[i] == openers[i + 1] == openers[i + 2] and openers[i] not in ("the", "a", "i")]
    add("W7", "signal", "Sentence length varies (CV >= 0.4) and no word opens three sentences in a row",
        (cv is None or cv >= 0.4) and not runs, f"CV {cv}, repeated openers: {sorted(set(runs)) or 'none'}")

    placeholders = re.findall(r"(?im)\[ivan\b[^\]\n]*\]?|^.*notes for ivan.*$|\bTODO\b|\bTKTK\b|\bXXX\b|lorem ipsum", text)
    add("X2", "gate", "No placeholders, TODOs or notes to Ivan", not placeholders, placeholders[:3] or "none")
    heads = re.findall(r"^#{2,4}\s+(.+)$", body, re.M)
    generic = [h for h in heads if re.fullmatch(TEMPLATE_HEADINGS, h.strip().rstrip(".:").lower())]
    add("S3m", "signal", "Headings aren't template labels", not generic, generic or f"{len(heads)} headings, none generic")

    if LEAK_CHECK.exists():
        leak = subprocess.run(["bash", str(LEAK_CHECK), str(path)], capture_output=True, text=True)
        add("X1", "gate", "leak-check.sh is clean", leak.returncode == 0, leak.stdout.strip()[:300] or "clean")

    notes = footnotes(body, sources)
    if notes is not None:
        problems, in_order, first_seen = notes
        add("F7", "gate", "Every [n] citation has a Sources entry and every entry is cited", not problems, problems or "intact")
        add("F7b", "signal", "Sources are numbered in order of first citation", in_order, f"first citations: {first_seen}")

    urls = sorted({u.rstrip(").,;") for u in re.findall(r"https?://[^\s)>\]`'\"“”]+", re.sub(r"```.*?```", "", text, flags=re.S))})
    urls = [u for u in urls if not re.search(r"//(?:[\w-]+\.)?example\.|//localhost|//127\.0\.0\.1", u)]
    if not offline:
        bad = [u for u in urls if not url_ok(u)]
        add("F6", "gate", "Every link resolves", not bad, f"{len(urls)} links, failing: {bad or 'none'}")

    words = len(body_prose.split())
    opening = "\n".join([p for p in re.split(r"\n\s*\n", body_prose) if p.strip() and not p.lstrip().startswith("#")][:4])
    metrics = {
        "words": words,
        "sentences": len(sents),
        "first_person_per_100_words": round(100 * len(re.findall(FIRST_PERSON, body_prose)) / max(words, 1), 1),
        "you_per_100_words": round(100 * len(re.findall(r"\byou(?:r|'re|’re|'ll|’ll|'ve|’ve)?\b", body_prose, re.I)) / max(words, 1), 1),
        "time_anchors": sorted(set(re.findall(TIME_ANCHOR, body_prose))),
        "time_anchor_in_opening": bool(re.search(TIME_ANCHOR, opening)),
        "versions_named": sorted(set(re.findall(VERSION, body_prose)))[:15],
        "code_blocks": len(re.findall(r"^```", body, re.M)) // 2,
        "headings": heads,
        "links": urls,
    }
    result = {"file": str(path), "metrics": metrics, "checks": checks,
              "gates_failed": [c["id"] for c in checks if c["kind"] == "gate" and not c["passed"]],
              "signals_raised": [c["id"] for c in checks if c["kind"] == "signal" and not c["passed"]]}
    if out:
        out.write_text(json.dumps(result, indent=2, ensure_ascii=False))
    for c in checks:
        print(f"{'PASS' if c['passed'] else 'FAIL'} {c['id']:4} {c['kind']:6} {c['text']}: {c['evidence'] if not c['passed'] else 'ok'}")
    print(json.dumps(metrics, ensure_ascii=False))


if __name__ == "__main__":
    main()
