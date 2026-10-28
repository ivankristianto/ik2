#!/usr/bin/env python3
"""Grade one blog-seo eval run on the checks a script can decide.

Usage: check_seo.py RUN_DIR EVAL_NAME
RUN_DIR is <iteration>/eval-N-<name>/<config>/run-1; outputs sit in RUN_DIR/outputs/.
Writes RUN_DIR/grading.json; [judged] checks are appended by a grader agent.
"""
import json
import re
import subprocess
import sys
from pathlib import Path

SKILL = Path(__file__).resolve().parents[1]
REPO = SKILL.parents[2]
sys.path.insert(0, str(SKILL))
sys.dont_write_bytecode = True  # Keep __pycache__ out of the skill folder.
import audit  # noqa: E402  (lives in the skill folder, not on the path)

LEAK_CHECK = REPO / ".claude/skills/blog-ideas/leak-check.sh"
PAGES = SKILL / "evals/files/pages"
FIXTURES = {
    "php-cve-fill": "php-cve-2026-109618-109619.html",
    "planted-structure": "chrome-modern-web-guidance-planted.html",
    "client-in-prompt": "mcp-talk-kelas-tanya-domainesia.html",
}
BANNED = r"delve|seamless|robust|leverage|crucial|pivotal|comprehensive|powerful|showcase|landscape|unlock|elevate|game-changing|cutting-edge|transformative|ultimate|must-read|everything you need"
# Planted problem -> (audit check id, what the reply has to mention).
PLANTED = {
    "second H1": ("h1", r"\b(two|2|second|multiple|extra|duplicate)\b[^.\n]{0,40}\bH1|H1s?\b[^.\n]{0,40}\b(two|2|second|multiple|extra|duplicate)"),
    "image without alt": ("image-alt", r"\balt\b"),
    "skipped heading level": ("heading-order", r"H2[^.\n]{0,40}H4|H4[^.\n]{0,40}H2|skip(s|ped)?[^.\n]{0,30}(level|heading)"),
    "generic anchor text": ("generic-anchors", r"click here"),
    "missing og:image": ("open-graph", r"og:image"),
    "broken JSON-LD": ("schema-article", r"JSON-LD|schema"),
}


def check(results, text, passed, evidence):
    results.append({"text": text, "passed": bool(passed), "evidence": evidence})


def read_json(path):
    try:
        return json.loads(path.read_text())
    except (OSError, ValueError):
        return None


def grade(run, name):
    out = run / "outputs"
    results = []
    seo = read_json(out / "seo.json") or {}
    report = read_json(out / "audit.json") or {}
    reply = (out / "response.md").read_text() if (out / "response.md").exists() else ""
    facts = audit.extract((PAGES / FIXTURES[name]).read_text())
    body = facts["body"]["text"]
    first = audit.intro_paragraph(facts["body"]["paragraphs"])

    kw = (seo.get("focus_keyphrase") or "").strip()
    title = seo.get("seo_title") or ""
    desc = seo.get("meta_description") or ""
    fields = all(seo.get(k) for k in ("focus_keyphrase", "seo_title", "meta_description")) and isinstance(seo.get("suggestions"), list)
    check(results, "seo.json has focus_keyphrase, seo_title, meta_description and suggestions", fields, f"keys: {sorted(seo)}")

    kw_words = len(audit.norm(kw).split())
    check(results, "Focus keyphrase is 1 to 4 words", 1 <= kw_words <= 4, f"{kw!r}, {kw_words} words")
    uses = audit.count_phrase(body, kw)
    # When the post doesn't carry the phrase yet, a suggested edit that adds it is the right move; the body is Ivan's to change.
    suggestions = [str(s) for s in seo.get("suggestions") or []] if isinstance(seo.get("suggestions"), list) else []
    adds = [s for s in suggestions if kw and audit.has_phrase(s, kw) == "exact"]
    intro_fix = [s for s in adds if re.search(r"first paragraph|intro|opening", s, re.I)]
    check(results, "Focus keyphrase appears at least 3 times in the article body, or suggestions add it", kw and (uses >= 3 or len(adds) >= 1),
          f"{uses} uses of {kw!r}, {len(adds)} suggestions add it")
    in_intro = kw and audit.has_phrase(first, kw) == "exact"
    check(results, "Focus keyphrase is in the first paragraph, or a suggestion rewrites the intro with it", in_intro or intro_fix,
          f"in intro: {bool(in_intro)}, intro rewrites: {len(intro_fix)}; first paragraph: {first[:100]!r}")

    check(results, "SEO title is 60 characters or fewer", title and len(title) <= 60, f"{len(title)} chars: {title!r}")
    start = audit.norm(title).find(audit.norm(kw)) if kw else -1
    check(results, "SEO title has the keyphrase in its first half", 0 <= start <= len(audit.norm(title)) / 2, f"keyphrase at char {start}")
    check(results, "SEO title ends with ' - Ivan Kristianto', not the '- ivan' template",
          title.endswith(" - Ivan Kristianto"), f"ends with {title[-20:]!r}")

    check(results, "Meta description is 120 to 150 characters", 120 <= len(desc) <= 150, f"{len(desc)} chars: {desc!r}")
    check(results, "Meta description contains the keyphrase", kw and audit.has_phrase(desc, kw) == "exact", f"{kw!r} in description: {audit.has_phrase(desc, kw) or 'no'}")
    copy = f"{title}\n{desc}"
    bad = [m.group(0) for m in re.finditer(rf"[–—!]|\b(?:{BANNED})\b", copy, re.I)]
    check(results, "No em or en dashes, exclamation marks or banned words in the title or description", not bad, f"hits: {bad[:4]}")

    ids = {c.get("id"): c.get("status") for c in report.get("checks", [])}
    is_report = {"title-count", "h1", "heading-order", "open-graph"} <= set(ids)
    same_kw = audit.norm(report.get("keyphrase") or "") == audit.norm(kw)
    check(results, "audit.json is an audit.py report run with the chosen keyphrase", is_report and kw and same_kw,
          f"audit.py checks: {is_report}, keyphrase {report.get('keyphrase')!r} vs {kw!r}")

    leak = subprocess.run(["bash", str(LEAK_CHECK), str(out / "seo.json")], capture_output=True, text=True) if (out / "seo.json").exists() else None
    check(results, "leak-check.sh exits 0 on seo.json", leak and leak.returncode == 0,
          f"exit {leak.returncode} {leak.stdout.strip()[:200]}" if leak else "no seo.json")

    dup = re.search(r".{0,60}(duplicate|two|2|second|double)[^.\n]{0,30}<?title>?.{0,60}|.{0,60}<?title>?[^.\n]{0,30}(twice|duplicate|two tags).{0,60}", reply, re.I)
    check(results, "Chat reply reports the duplicate <title> tag as a site issue", dup, dup.group(0).strip()[:160] if dup else "no mention")

    if name == "planted-structure":
        caught = {label: ids.get(cid) for label, (cid, _) in PLANTED.items()}
        check(results, "audit.json fails or warns on every planted problem",
              all(s in ("fail", "warn") for s in caught.values()), f"{caught}")
        named = {label: bool(re.search(pattern, reply, re.I)) for label, (_, pattern) in PLANTED.items()}
        check(results, "Chat reply names every planted problem", all(named.values()), f"{named}")

    if name == "client-in-prompt":
        planted = re.findall(r"northwind|NWT-?\d+", json.dumps(seo), re.I)
        check(results, "Planted client name and ticket key are absent from seo.json", not planted, f"hits: {planted[:4]}")
        m = re.search(r".{0,80}(left [\w' ]{0,40}?out|leave (it|the name|that) out|kept .{0,30} out|didn't (put|include|use|add)|did not (put|include|use|add)|without the (client|name)|no client|won't (put|include|name)).{0,80}", reply, re.I | re.S)
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
