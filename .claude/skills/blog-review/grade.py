#!/usr/bin/env python3
"""Score a blog review: merge the judge's verdicts with check.py's gates, apply caps, write the report.

Usage: python3 -I grade.py REVIEW.json CHECK.json OUT_BASE
REVIEW.json is what the judge writes (shape in references/rubric.md). Writes OUT_BASE.json and OUT_BASE.md.
"""
import json
import sys
from pathlib import Path

CATEGORIES = {
    "R": ("Future-self recall", 30),
    "F": ("Facts and sources", 25),
    "S": ("Structure", 15),
    "V": ("Voice", 15),
    "W": ("Wording", 15),
}
ITEMS = {
    "R1": "A date or time anchors when this happened",
    "R2": "Says why he wrote it or what prompted it",
    "R3": "Names the setting: project, stack, versions",
    "R4": "Shows what he actually ran or did, with the result",
    "R5": "Says what he chose and why, dead ends included",
    "R6": "States the outcome",
    "R7": "Says what is still open or untested, in his words",
    "R8": "The 30-second test: title and first paragraph say what this post records",
    "F1": "No claim is contradicted by its source or by the code",
    "F2": "No claim overstates its source",
    "F3": "Every non-obvious technical claim has a source that says it",
    "F4": "First-person claims trace to a research file or to Ivan",
    "F5": "Sources are primary, and code links are pinned",
    "F6": "Every link resolves",
    "F7": "Citations and the Sources list match",
    "S1": "The answer arrives in the first three paragraphs",
    "S2": "Order follows what happened, or the steps a reader takes",
    "S3": "Headings name things from this story",
    "S4": "Steps are numbered and a reader could follow them",
    "S5": "Sections stop when the content stops",
    "S6": "The close hands over to the reader, and Sources come last",
    "S7": "Length fits what the post has to say",
    "V1": "Opens in a real situation, not a thesis or a hypothetical",
    "V2": "First person: his experience, not a generic tutorial",
    "V3": "Plain talk and contractions, like he'd say it to a colleague",
    "V4": "Talks to one reader and gives them something to do",
    "V5": "A regular reader would believe Ivan wrote it (judge scores 1 to 5, pass at 4)",
    "W1": "No em or en dashes",
    "W2": "No banned vocabulary",
    "W3": "No giveaway phrases",
    "W4": "No canned contrast formulas or slogan lines",
    "W5": "No hypothetical scaffolding in place of his real case",
    "W6": "No faceless provenance that hides who did the work",
    "W7": "Rhythm varies; no repeated sentence shapes",
    "W8": "No filler: every sentence carries a fact, a step or a choice",
}
MECHANICAL = {"W1", "W2", "W3", "F6"}
CLAIM_ITEMS = {"F1": "contradicted", "F2": "overstated", "F3": "unsupported"}
POINTS = {"pass": 1.0, "partial": 0.5, "fail": 0.0}
LETTERS = [(90, "A"), (80, "B"), (70, "C"), (60, "D"), (0, "F")]


def letter(score):
    return next(l for floor, l in LETTERS if score >= floor)


def main():
    if len(sys.argv) != 4:
        sys.exit(__doc__)
    review = json.loads(Path(sys.argv[1]).read_text())
    check = json.loads(Path(sys.argv[2]).read_text())
    base = Path(sys.argv[3])
    gates = {c["id"]: c for c in check["checks"]}
    judged = {i["id"]: i for i in review.get("items", [])}
    claims = review.get("claims", [])

    items = {}
    for item_id, text in ITEMS.items():
        if item_id in MECHANICAL and item_id in gates:
            g = gates[item_id]
            items[item_id] = {"verdict": "pass" if g["passed"] else "fail", "evidence": g["evidence"], "fix": judged.get(item_id, {}).get("fix", "")}
        elif item_id == "F7" and "F7" in gates:
            g = gates["F7"]
            items[item_id] = {"verdict": "pass" if g["passed"] else "fail", "evidence": g["evidence"], "fix": judged.get(item_id, {}).get("fix", "")}
        elif item_id in CLAIM_ITEMS:
            bad = [c for c in claims if c["verdict"] == CLAIM_ITEMS[item_id]]
            verdict = "pass" if not bad else ("fail" if item_id == "F1" or len(bad) > 1 else "partial")
            items[item_id] = {"verdict": verdict, "evidence": "; ".join(c["claim"][:90] for c in bad) or f"{len(claims)} claims checked",
                              "fixes": [f"{c['claim'][:70]}: {c['fix']}" for c in bad if c.get("fix")]}
        elif item_id in judged:
            items[item_id] = judged[item_id]
        elif item_id == "F7":
            items[item_id] = {"verdict": "pass", "evidence": "no numbered citations", "fix": ""}
        elif item_id == "F6":
            items[item_id] = {"verdict": "partial", "evidence": "links not checked (check.py ran --offline)", "fix": ""}
        else:
            items[item_id] = {"verdict": "fail", "evidence": "not judged", "fix": ""}
        items[item_id]["text"] = text

    scores, total = {}, 0.0
    for key, (name, weight) in CATEGORIES.items():
        ids = [i for i in items if i.startswith(key)]
        got = sum(POINTS[items[i]["verdict"]] for i in ids) / len(ids)
        scores[key] = {"name": name, "weight": weight, "score": round(weight * got, 1),
                       "passed": sum(items[i]["verdict"] == "pass" for i in ids), "of": len(ids)}
        total += weight * got
    total = round(total, 1)

    caps, blockers = [], []
    if "X1" in gates and not gates["X1"]["passed"]:
        caps.append((0, "leak-check hit: client or private detail in the post"))
        blockers.append("Remove the leaked detail before anything else.")
    if "X2" in gates and not gates["X2"]["passed"]:
        caps.append((59, "placeholder or note to Ivan left in the post"))
    if any(c["verdict"] == "contradicted" for c in claims):
        caps.append((79, "a claim is contradicted by its source"))
    if items["W1"]["verdict"] == "fail":
        caps.append((89, "em or en dash in the prose"))
    if items["F6"]["verdict"] == "fail":
        caps.append((89, "a link doesn't resolve"))
    capped = min([total] + [c for c, _ in caps])
    grade = letter(capped)
    ask = [c for c in claims if c["verdict"] == "ask-ivan"]
    if blockers or grade in ("D", "F"):
        verdict = "not ready"
    elif grade == "A" and not ask:
        verdict = "ready"
    else:
        verdict = "ready after fixes"

    lost = sorted(((CATEGORIES[i[0]][1] / sum(1 for j in items if j[0] == i[0])) * (1 - POINTS[v["verdict"]]), i)
                  for i, v in items.items() if v["verdict"] != "pass")
    fixes = [f"{i}: {f}" for _, i in reversed(lost) for f in (items[i].get("fixes") or [items[i].get("fix")]) if f]
    questions = [f"{i}: {v['ask']}" for i, v in items.items() if v.get("ask") and v["verdict"] != "pass"]

    result = {"post": review.get("post", {}), "grade": grade, "score": capped, "raw_score": total, "verdict": verdict,
              "caps": [r for _, r in caps], "categories": scores, "items": items, "claims": claims,
              "cold_read": review.get("cold_read", {}), "from_ivan": review.get("from_ivan", {}), "signals": {c["id"]: c["evidence"] for c in check["checks"] if c["kind"] == "signal" and not c["passed"]},
              "metrics": check.get("metrics", {}), "top_fixes": fixes[:8], "questions": questions}
    base.with_suffix(".json").write_text(json.dumps(result, indent=2, ensure_ascii=False))
    base.with_suffix(".md").write_text(render(result, ask))
    print(f"{grade} ({capped}/100, raw {total}) {verdict}; caps: {result['caps'] or 'none'}")


def render(r, ask):
    post = r["post"]
    out = [f"# Review: {post.get('title', '')}", "",
           f"**Grade {r['grade']}** ({r['score']}/100{', raw ' + str(r['raw_score']) if r['score'] != r['raw_score'] else ''}), {r['verdict']}.", ""]
    for key in ("status", "url", "edit_url", "read_via", "reviewed"):
        if post.get(key):
            out.append(f"- {key.replace('_', ' ')}: {post[key]}")
    if r["caps"]:
        out += ["", "Caps applied: " + "; ".join(r["caps"]) + "."]
    out += ["", "| Category | Score | Items passed |", "|---|---|---|"]
    out += [f"| {c['name']} | {c['score']} / {c['weight']} | {c['passed']} of {c['of']} |" for c in r["categories"].values()]
    cold = r["cold_read"]
    if cold:
        out += ["", "## Cold read", ""]
        labels = {"what_i_remember": "What I remember", "lost_at": "Where I got lost", "question_left": "The question left"}
        out += [f"- **{labels.get(k, k)}:** {v if isinstance(v, str) else '; '.join(v)}" for k, v in cold.items()]
    if r["from_ivan"]:
        out += ["", "## From Ivan", ""] + [f"- **{k}:** {val}" for k, val in r["from_ivan"].items()]
    if r["top_fixes"]:
        out += ["", "## Fix first", ""] + [f"{n}. {f}" for n, f in enumerate(r["top_fixes"], 1)]
    if ask or r["questions"]:
        out += ["", "## Ask Ivan", ""] + [f"- {q}" for q in r["questions"]]
        out += [f"- Did this happen? {c['claim']}" + (f" ({c['evidence']})" if c.get("evidence") else "") for c in ask]
    if r["claims"]:
        out += ["", "## Claims", "", "| Claim | Source | Verdict | Evidence |", "|---|---|---|---|"]
        out += [f"| {c['claim']} | {c.get('source', '')} | {c['verdict']} | {c.get('evidence', '')} |".replace("\n", " ") for c in r["claims"]]
    out += ["", "## Every item", ""]
    for item_id, i in r["items"].items():
        ev = i["evidence"] if isinstance(i["evidence"], str) else json.dumps(i["evidence"], ensure_ascii=False)
        todo = "" if i["verdict"] == "pass" else "".join(f" Fix: {f}" for f in (i.get("fixes") or [i.get("fix")]) if f) + (f" Ask: {i['ask']}" if i.get("ask") else "")
        out.append(f"- **{item_id} {i['verdict']}**: {i['text']}. {ev}{todo}")
    if r["signals"]:
        out += ["", "## Script signals", ""] + [f"- {k}: {v}" for k, v in r["signals"].items()]
    return "\n".join(out) + "\n"


if __name__ == "__main__":
    main()
