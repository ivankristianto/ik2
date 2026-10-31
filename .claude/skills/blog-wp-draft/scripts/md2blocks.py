"""Turn a blog draft (.claude/blog/drafts/<slug>.md) into the Gutenberg block markup this site uses.

Usage: python3 -I md2blocks.py <draft.md> [--page <out.html>]
Prints the blocks. --page also writes a stand-in post page that blog-seo's audit.py can read.
Markdown it can't map to a block stops the run with the line number, instead of landing as raw text.
"""
import html
import json
import re
import sys
from pathlib import Path

REPO = Path(__file__).resolve().parents[4]
LANGUAGES_JSON = REPO / "wp-content/plugins/ik2/src/code-highlight/languages.json"
FENCE_ALIASES = {"sh": "bash", "shell": "bash", "zsh": "bash", "console": "bash", "js": "javascript",
                 "ts": "typescript", "yml": "yaml", "dockerfile": "docker", "htm": "html"}
PLAIN_FENCES = {"", "text", "txt", "plain", "output"}
SITE = "https://www.ivankristianto.com"


class DraftError(ValueError):
    pass


def languages():
    return {entry["value"] for entry in json.loads(LANGUAGES_JSON.read_text())}


def split_frontmatter(text):
    m = re.match(r"---\n(.*?)\n---\n(.*)", text, re.S)
    if not m:
        raise DraftError("draft has no --- frontmatter block")
    meta = {}
    for line in m.group(1).splitlines():
        if not line.strip():
            continue
        key, _, value = line.partition(":")
        value = value.strip()
        if value.startswith("[") and value.endswith("]"):
            meta[key.strip()] = [v.strip().strip("'\"") for v in value[1:-1].split(",") if v.strip()]
        elif value.startswith('"'):
            meta[key.strip()] = json.loads(value)
        else:
            meta[key.strip()] = value.strip("'")
    return meta, m.group(2)


def read_seo(path):
    """Parse seo.txt lines like 'SEO title (57): ...' or 'title: ...' into keyphrase, title, description."""
    keys = {"focus keyphrase": "keyphrase", "keyphrase": "keyphrase", "seo title": "title", "title": "title",
            "meta description": "description", "description": "description"}
    out = {}
    for line in Path(path).read_text().splitlines():
        m = re.match(r"\s*([A-Za-z ]+?)\s*(?:\(\d+\))?:\s*(.+)$", line)
        if m and m.group(1).lower() in keys:
            out[keys[m.group(1).lower()]] = m.group(2).strip()
    return out


def autolink(match):
    url, tail = match.group(1), ""
    # Sentence punctuation after a bare URL belongs to the sentence; keep ")" only when the URL opened one.
    while url and (url[-1] in ".,;:!?" or (url[-1] == ")" and url.count("(") < url.count(")"))):
        url, tail = url[:-1], url[-1] + tail
    return f'<a href="{url}">{url}</a>{tail}'


def inline(text):
    text = html.escape(text, quote=False)
    codes = []
    text = re.sub(r"`([^`]+)`", lambda m: codes.append(m.group(1)) or f"\x00{len(codes) - 1}\x00", text)
    text = re.sub(r"\[([^\]]+)\]\((https?://[^)]+)\)", r'<a href="\2">\1</a>', text)
    text = re.sub(r'(?<![">])(https://[^\s<]+)', autolink, text)
    return re.sub(r"\x00(\d+)\x00", lambda m: "<code>" + codes[int(m.group(1))] + "</code>", text)


def check_inline(text, lineno):
    bare = re.sub(r"`[^`]*`", "", text)
    if "**" in bare or re.search(r"(?<!\w)__\S", bare):
        raise DraftError(f"line {lineno}: bold markup is not converted; drop it or ask for support")
    if "![" in bare:
        raise DraftError(f"line {lineno}: images are not converted; add them in the editor")


def code_language(tag, lineno, known):
    tag = tag.strip().lower()
    if tag in PLAIN_FENCES:
        return None
    tag = FENCE_ALIASES.get(tag, tag)
    if tag not in known:
        raise DraftError(f"line {lineno}: fence language {tag!r} is not in the highlighter's languages.json")
    return tag


def block(name, inner, attrs=None):
    head = f"<!-- wp:{name} {json.dumps(attrs, separators=(',', ':'))} -->" if attrs else f"<!-- wp:{name} -->"
    return f"{head}\n{inner}\n<!-- /wp:{name} -->"


def convert(body, start_line=1):
    known = languages()
    out, para, quote, items = [], [], [], []
    state = {"list": None, "first": 1}
    fence = None

    def flush_para():
        if para:
            out.append(block("paragraph", "<p>" + inline(" ".join(para)) + "</p>"))
            para.clear()

    def flush_quote():
        if quote:
            inner = block("paragraph", "<p>" + inline(" ".join(quote)) + "</p>")
            out.append(block("quote", f'<blockquote class="wp-block-quote">{inner}</blockquote>'))
            quote.clear()

    def flush_list():
        if state["list"]:
            lis = "\n\n".join(block("list-item", f"<li>{inline(i)}</li>") for i in items)
            if state["list"] == "ol":
                attrs = {"ordered": True}
                open_tag = '<ol class="wp-block-list">'
                if state["first"] > 1:
                    attrs["start"] = state["first"]
                    open_tag = f'<ol start="{state["first"]}" class="wp-block-list">'
                out.append(block("list", f"{open_tag}{lis}</ol>", attrs))
            else:
                out.append(block("list", f'<ul class="wp-block-list">{lis}</ul>'))
        state["list"] = None
        items.clear()

    def flush_all():
        flush_para()
        flush_quote()
        flush_list()

    for n, line in enumerate(body.split("\n"), start_line):
        s = line.strip()
        if fence is not None:
            if s.startswith("```"):
                text = html.escape("\n".join(fence["lines"]), quote=False)
                attrs = {"language": fence["lang"]} if fence["lang"] else None
                out.append(block("code", f'<pre class="wp-block-code"><code>{text}</code></pre>', attrs))
                fence = None
            else:
                fence["lines"].append(line[len(fence["indent"]):] if line.startswith(fence["indent"]) else line)
            continue
        if s.startswith("```"):
            flush_para()
            flush_quote()
            flush_list()
            fence = {"lang": code_language(s[3:], n, known), "indent": line[: len(line) - len(line.lstrip())], "lines": []}
            continue
        if not s:
            flush_para()
            flush_quote()
            continue
        if re.match(r"\s*\|", line) or re.fullmatch(r"(-{3,}|\*{3,})", s) or (s.startswith("<") and not s.startswith("<http")):
            raise DraftError(f"line {n}: tables, rules and raw HTML are not converted: {s[:40]!r}")
        check_inline(s, n)
        heading = re.match(r"(#{1,6}) (.*)", s)
        if heading:
            level = len(heading.group(1))
            if level == 1:
                raise DraftError(f"line {n}: H1 belongs in the frontmatter title, use ## in the body")
            flush_all()
            tag = f"h{level}"
            out.append(block("heading", f'<{tag} class="wp-block-heading">{inline(heading.group(2))}</{tag}>',
                             {"level": level} if level != 2 else None))
            continue
        if re.match(r"\s{2,}(- |\d+\. )", line):
            raise DraftError(f"line {n}: nested lists are not converted; flatten the list")
        if s.startswith("> "):
            flush_para()
            flush_list()
            quote.append(s[2:])
            continue
        numbered = re.match(r"(\d+)\. (.*)", s)
        if numbered:
            flush_para()
            if state["list"] != "ol":
                flush_list()
                state["list"], state["first"] = "ol", int(numbered.group(1))
            items.append(numbered.group(2))
            continue
        if s.startswith("- "):
            flush_para()
            if state["list"] != "ul":
                flush_list()
                state["list"] = "ul"
            items.append(s[2:])
            continue
        if state["list"] and items and line.startswith(("  ", "\t")):
            items[-1] += " " + s
            continue
        flush_list()
        flush_quote()
        para.append(s)
    if fence is not None:
        raise DraftError("a code fence is never closed")
    flush_all()
    return "\n\n".join(out)


def load_draft(path):
    """Return (frontmatter, blocks) for a draft file; the body's line numbers match the file."""
    text = Path(path).read_text()
    meta, body = split_frontmatter(text)
    for key in ("title", "slug", "excerpt", "category"):
        if not meta.get(key):
            raise DraftError(f"frontmatter is missing {key}")
    if meta.get("status", "draft") != "draft":
        raise DraftError("frontmatter status must be draft; publishing is Ivan's call")
    if not re.fullmatch(r"[a-z0-9]+(-[a-z0-9]+)*", meta["slug"]):
        raise DraftError(f"slug {meta['slug']!r} is not lowercase kebab-case")
    first_body_line = text[: len(text) - len(body)].count("\n") + 1
    return meta, convert(body, first_body_line)


def page(meta, blocks, seo, alt):
    """A stand-in for the rendered post, shaped like the theme's markup so audit.py can read it."""
    url = f"{SITE}/{meta['slug']}/"
    title = seo.get("title") or meta["title"]
    return (f'<!doctype html><html lang="en-US"><head><meta charset="utf-8">'
            f'<meta name="viewport" content="width=device-width, initial-scale=1"><title>{html.escape(title)}</title>'
            f'<meta name="description" content="{html.escape(seo.get("description", ""))}"><link rel="canonical" href="{url}">'
            f'</head><body><main><article><h1>{html.escape(meta["title"])}</h1>'
            f'<figure class="ik-article__featured"><img src="feature.webp" alt="{html.escape(alt)}"></figure>'
            f'<div class="ik-article__body">{blocks}</div></article></main></body></html>\n')


def main(argv):
    if len(argv) not in (2, 4) or (len(argv) == 4 and argv[2] != "--page"):
        sys.exit(__doc__)
    draft = Path(argv[1])
    try:
        meta, blocks = load_draft(draft)
    except DraftError as err:
        sys.exit(f"{draft}: {err}")
    if len(argv) == 4:
        assets = draft.with_suffix("")
        seo = read_seo(assets / "seo.txt") if (assets / "seo.txt").exists() else {}
        alt = (assets / "alt.txt").read_text().strip() if (assets / "alt.txt").exists() else ""
        Path(argv[3]).write_text(page(meta, blocks, seo, alt))
    print(blocks)


if __name__ == "__main__":
    main(sys.argv)
