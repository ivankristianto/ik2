#!/usr/bin/env python3
"""On-page SEO audit of one rendered ivankristianto.com article.

Usage: audit.py (URL | FILE.html | --facts FACTS.json) [--keyphrase "phrase"] [--preview] [--json OUT.json]
Prints one pass/warn/fail line per check. FACTS.json is what extract.js returns for a draft preview in Chrome.
"""
import argparse
import json
import re
import sys
import urllib.request
from html.parser import HTMLParser
from urllib.parse import urlparse

HOST = "www.ivankristianto.com"
UA = "Mozilla/5.0 (Macintosh) ik2-blog-seo"  # Cloudflare answers 403 to Python's default agent.
VOID = {"area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta", "source", "track", "wbr"}
GENERIC_ANCHORS = {"click here", "here", "read more", "more", "this", "this link", "link", "this page", "this post"}
OG_REQUIRED = ["og:title", "og:description", "og:image", "og:url", "og:type"]
ARTICLE_TYPES = {"Article", "BlogPosting", "TechArticle", "NewsArticle"}


class Extractor(HTMLParser):
    """Collects the facts the checks need; the same shape extract.js builds in the browser."""

    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.facts = {"lang": None, "titles": [], "meta": {}, "canonical": None, "jsonld": [], "headings": [],
                      "body": {"paragraphs": [], "text": "", "images": [], "links": []}, "featured_alt": None}
        self.stack = []
        self.body_depth = self.main_depth = self.skip_depth = None
        self.featured_depth = None
        self.capture = []
        self.text = []
        self.in_title = self.in_jsonld = self.in_head = self.in_main = False
        self.jsonld = []

    def handle_starttag(self, tag, attrs):
        a = {k: (v if v is not None else "") for k, v in attrs}
        classes = a.get("class", "").split()
        if tag == "html":
            self.facts["lang"] = a.get("lang")
        elif tag == "head":
            self.in_head = True
        elif tag == "title" and self.in_head:
            self.in_title = True
            self.facts["titles"].append("")
        elif tag == "meta" and ("name" in a or "property" in a):
            self.facts["meta"][(a.get("name") or a.get("property")).lower()] = a.get("content", "")
        elif tag == "link" and "canonical" in a.get("rel", "").split():
            self.facts["canonical"] = a.get("href")
        elif tag == "script" and a.get("type") == "application/ld+json":
            self.in_jsonld, self.jsonld = True, []

        if tag in VOID:
            self._void(tag, a, classes)
            return
        self.stack.append(tag)
        depth = len(self.stack)
        if tag == "main" and self.main_depth is None:
            self.main_depth = depth
            self.in_main = True
        if "ik-article__body" in classes and self.body_depth is None:
            self.body_depth = depth
        if "ik-article__featured" in classes:
            self.featured_depth = depth
        # Citation labels and source previews are theme chrome, not the writer's text.
        if self.skip_depth is None and ("popover" in a or "ik-cite__label" in classes):
            self.skip_depth = depth
        if self.skip_depth is None and (re.fullmatch(r"h[1-6]", tag) or (tag == "p" and self._in_body()) or tag == "a"):
            self.capture.append({"tag": tag, "depth": depth, "attrs": a, "text": []})

    def _void(self, tag, a, classes):
        if tag != "img":
            return
        alt = a.get("alt")
        decorative = a.get("role") == "presentation" or a.get("aria-hidden") == "true"
        # An image's alt is the accessible name of the link around it.
        for item in self.capture:
            if item["tag"] == "a" and alt:
                item["text"].append(f" {alt} ")
        if self.featured_depth is not None:
            self.facts["featured_alt"] = alt
        elif self._in_body() and self.skip_depth is None:
            self.facts["body"]["images"].append({"src": a.get("src", ""), "alt": alt, "decorative": decorative})

    def handle_endtag(self, tag):
        if tag == "title":
            self.in_title = False
        if tag == "head":
            self.in_head = False
        if tag == "script" and self.in_jsonld:
            self.in_jsonld = False
            self.facts["jsonld"].append("".join(self.jsonld))
        if tag not in self.stack:
            return
        while self.stack:
            depth = len(self.stack)
            open_tag = self.stack.pop()
            self._close(open_tag, depth)
            if open_tag == tag:
                break

    def _close(self, tag, depth):
        if self.capture and self.capture[-1]["depth"] == depth:
            item = self.capture.pop()
            text = re.sub(r"\s+", " ", "".join(item["text"])).strip()
            if re.fullmatch(r"h[1-6]", tag):
                self.facts["headings"].append([int(tag[1]), text, self.in_main])
            elif tag == "p":
                self.facts["body"]["paragraphs"].append(text)
            elif tag == "a" and self._in_body():
                a = item["attrs"]
                self.facts["body"]["links"].append({"href": a.get("href"), "text": text, "role": a.get("role", ""),
                                                    "label": a.get("aria-label", ""), "rel": a.get("rel", "")})
        if depth == self.skip_depth:
            self.skip_depth = None
        if depth == self.featured_depth:
            self.featured_depth = None
        if depth == self.body_depth:
            self.body_depth = None
        if depth == self.main_depth:
            self.in_main = False

    def handle_data(self, data):
        if self.in_title:
            self.facts["titles"][-1] += data
        if self.in_jsonld:
            self.jsonld.append(data)
        if self.skip_depth is not None:
            return
        for item in self.capture:
            item["text"].append(data)
        if self._in_body():
            self.text.append(data)

    def _in_body(self):
        return self.body_depth is not None

    def result(self):
        self.facts["titles"] = [t.strip() for t in self.facts["titles"]]
        self.facts["body"]["text"] = re.sub(r"\s+", " ", " ".join(self.text)).strip()
        return self.facts


def extract(html):
    parser = Extractor()
    parser.feed(html)
    parser.close()
    facts = parser.result()
    # Headings in site chrome (footer, palette) don't count toward the article outline.
    facts["headings"] = [[lvl, text, True] for lvl, text, in_main in facts["headings"] if in_main or lvl == 1]
    return facts


def load(source):
    if re.match(r"https?://", source):
        req = urllib.request.Request(source, headers={"User-Agent": UA})
        with urllib.request.urlopen(req, timeout=30) as resp:
            return resp.read().decode("utf-8", "replace")
    with open(source, encoding="utf-8") as fh:
        return fh.read()


def norm(text):
    return re.sub(r"\s+", " ", re.sub(r"[^\w\s-]", " ", (text or "").lower())).strip()


def has_phrase(text, phrase):
    """'exact' when the phrase appears, 'words' when every word does, else ''."""
    text, phrase = f" {norm(text)} ", norm(phrase)
    if not phrase:
        return ""
    if f" {phrase} " in text:
        return "exact"
    return "words" if all(f" {w} " in text for w in phrase.split()) else ""


def count_phrase(text, phrase):
    phrase = norm(phrase)
    return len(re.findall(rf"(?<!\w){re.escape(phrase)}(?!\w)", norm(text))) if phrase else 0


def intro_paragraph(paragraphs):
    """First paragraph of real prose; skips short lines such as an "Updated <date>" callout title."""
    paragraphs = [p for p in paragraphs if p]
    return next((p for p in paragraphs if len(p.split()) >= 10), paragraphs[0] if paragraphs else "")


def article_node(jsonld):
    nodes = []
    for raw in jsonld:
        data = json.loads(raw)
        for item in data if isinstance(data, list) else [data]:
            nodes.extend(item.get("@graph", [item]))
    for node in nodes:
        types = node.get("@type", [])
        if set([types] if isinstance(types, str) else types) & ARTICLE_TYPES:
            return node
    return None


def run_checks(facts, keyphrase=None, preview=False):
    checks = []
    meta = facts.get("meta", {})
    body = facts.get("body", {})
    titles = facts.get("titles", [])
    title, desc = (titles[0] if titles else ""), meta.get("description", "")

    def add(cid, status, detail):
        checks.append({"id": cid, "status": status, "detail": detail})

    def phrase_in(cid, label, text, required=False):
        if not keyphrase:
            return
        found = has_phrase(text, keyphrase)
        status = "pass" if found == "exact" else ("warn" if found or not required else "fail")
        add(cid, status, f"keyphrase {'found' if found == 'exact' else 'words only' if found else 'missing'} in {label}")

    add("title-count", "pass" if len(titles) == 1 else "fail", f"{len(titles)} <title> tags" + (" (Yoast's and core's block-template title)" if len(titles) > 1 else ""))
    add("title", "fail" if not title else "pass" if 30 <= len(title) <= 60 else "warn", f"{len(title)} chars: {title!r}")
    if re.search(r"[-|]\s*ivan$", title, re.I):
        add("title-sitename", "warn", "ends with the '- ivan' template; write ' - Ivan Kristianto' by hand")
    phrase_in("title-keyphrase", "the title tag", title, required=True)
    if keyphrase and has_phrase(title, keyphrase) == "exact":
        start = norm(title).find(norm(keyphrase))
        add("title-keyphrase-position", "pass" if start <= len(norm(title)) / 2 else "warn", f"keyphrase starts at char {start}")

    add("meta-description", "fail" if not desc else "pass" if 120 <= len(desc) <= 156 else "warn", f"{len(desc)} chars: {desc[:90]!r}")
    phrase_in("meta-description-keyphrase", "the meta description", desc, required=True)

    canonical = facts.get("canonical")
    add("canonical", "fail" if not canonical else "pass" if canonical.startswith("https://") else "warn", canonical or "missing")
    robots = meta.get("robots", "")
    noindex = "noindex" in robots
    add("robots", "fail" if noindex and not preview else "pass", f"{robots or 'none'}{' (preview pages are noindex)' if noindex and preview else ''}")

    h1s = [text for lvl, text, _ in facts.get("headings", []) if lvl == 1]
    add("h1", "pass" if len(h1s) == 1 else "fail", f"{len(h1s)} H1: {h1s[:3]}")
    if h1s:
        phrase_in("h1-keyphrase", "the H1", h1s[0])
    skips, last = [], 1
    for lvl, text, _ in facts.get("headings", []):
        if lvl > last + 1:
            skips.append(f"h{last} -> h{lvl} {text[:40]!r}")
        last = lvl
    add("heading-order", "fail" if skips else "pass", "; ".join(skips) or "no skipped levels")
    subheads = [text for lvl, text, _ in facts.get("headings", []) if lvl in (2, 3)]
    if keyphrase:
        hits = [t for t in subheads if has_phrase(t, keyphrase)]
        add("subheading-keyphrase", "pass" if hits else "warn", f"{len(hits)} of {len(subheads)} H2/H3 carry it")

    intro = intro_paragraph(body.get("paragraphs", []))
    if intro:
        phrase_in("intro-keyphrase", "the first paragraph", intro, required=True)
    words = len(re.findall(r"\w+", body.get("text", "")))
    add("word-count", "pass" if words >= 300 else "warn", f"{words} words in the article body")
    if keyphrase and words:
        hits = count_phrase(body.get("text", ""), keyphrase)
        density = hits * len(norm(keyphrase).split()) / words * 100
        add("keyphrase-density", "pass" if hits >= 3 and density <= 3 else "warn", f"{hits} uses, {density:.1f}% of {words} words")

    missing = [i["src"].rsplit("/", 1)[-1] for i in body.get("images", []) if not i.get("decorative") and not (i.get("alt") or "").strip()]
    add("image-alt", "fail" if missing else "pass", f"no alt: {missing[:5]}" if missing else f"{len(body.get('images', []))} body images, all with alt")
    featured = facts.get("featured_alt")
    if featured is not None:
        add("featured-alt", "pass" if featured.strip() else "fail", f"{featured[:90]!r}")

    links = [l for l in body.get("links", []) if l.get("role") not in ("doc-noteref", "doc-backlink")]
    internal, external, empty, generic = [], [], [], []
    for link in links:
        href = link.get("href") or ""
        if not href or href.startswith("#"):
            continue
        host = urlparse(href).netloc
        (internal if not host or host.endswith("ivankristianto.com") else external).append(href)
        text = norm(link.get("text"))
        if not text and not link.get("label"):
            empty.append(href)
        elif text in GENERIC_ANCHORS:
            generic.append(f"{link.get('text')!r} -> {href}")
    add("internal-links", "pass" if internal else "warn", f"{len(internal)} links to other pages on the blog")
    add("external-links", "pass" if external else "warn", f"{len(external)} outbound links")
    add("empty-anchors", "fail" if empty else "pass", f"no text: {empty[:3]}" if empty else "every link has text")
    add("generic-anchors", "warn" if generic else "pass", "; ".join(generic[:3]) or "no generic anchor text")

    missing_og = [k for k in OG_REQUIRED if not meta.get(k)]
    add("open-graph", "fail" if missing_og else "pass", f"missing {missing_og}" if missing_og else "og title, description, image, url, type")
    add("twitter-card", "pass" if meta.get("twitter:card") else "warn", meta.get("twitter:card") or "missing")

    try:
        node = article_node(facts.get("jsonld", []))
        lacking = [k for k in ("headline", "datePublished", "author", "image") if node and not node.get(k)]
        status = "fail" if node is None else "warn" if lacking else "pass"
        add("schema-article", status, "no Article/BlogPosting node" if node is None else f"missing {lacking}" if lacking else f"{node.get('@type')} with headline, dates, author, image")
    except (ValueError, AttributeError) as err:
        add("schema-article", "fail", f"JSON-LD does not parse: {err}")

    add("lang", "pass" if facts.get("lang") else "fail", facts.get("lang") or "missing")
    add("viewport", "pass" if meta.get("viewport") else "fail", meta.get("viewport") or "missing")

    slug_source = canonical or meta.get("og:url") or ""
    slug = urlparse(slug_source).path.strip("/").rsplit("/", 1)[-1]
    if slug and not slug.isdigit() and "preview" not in slug_source:
        slug_words = slug.split("-")
        add("slug", "pass" if len(slug_words) <= 6 else "warn", f"{slug!r}, {len(slug_words)} words")
        phrase_in("slug-keyphrase", "the slug", slug.replace("-", " "))

    summary = {s: sum(c["status"] == s for c in checks) for s in ("pass", "warn", "fail")}
    return {"source": facts.get("source"), "keyphrase": keyphrase, "checks": checks, "summary": summary}


def main():
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument("source", nargs="?", help="Published URL or saved HTML file")
    parser.add_argument("--facts", help="JSON from extract.js, for draft previews")
    parser.add_argument("--keyphrase")
    parser.add_argument("--preview", action="store_true", help="Don't fail the noindex every preview carries")
    parser.add_argument("--json", help="Write the full report here")
    args = parser.parse_args()
    if not args.source and not args.facts:
        parser.error("give a URL, an HTML file or --facts")

    if args.facts:
        with open(args.facts, encoding="utf-8") as fh:
            facts = json.load(fh)
    else:
        facts = extract(load(args.source))
    facts.setdefault("source", args.source or args.facts)
    report = run_checks(facts, args.keyphrase, args.preview)

    for c in report["checks"]:
        print(f"{c['status'].upper():4}  {c['id']:28} {c['detail']}")
    s = report["summary"]
    print(f"\n{s['pass']} pass, {s['warn']} warn, {s['fail']} fail")
    if args.json:
        with open(args.json, "w", encoding="utf-8") as fh:
            json.dump(report, fh, indent=2)
    return 1 if s["fail"] else 0


if __name__ == "__main__":
    sys.exit(main())
