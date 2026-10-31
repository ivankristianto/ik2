"""Fetch one ivankristianto.com post over REST and save it as Markdown for review.

Usage: python3 -I fetch.py <edit link | ?p= link | post URL | slug | ID> <out.md>
Drafts need IK2_PROD_USER and IK2_PROD_APP_PASSWORD in the repo's .env. Exit 3 means
the post isn't readable this way; read it through Chrome instead (SKILL.md, step 1).
"""
import base64
import html
import json
import re
import sys
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

REPO = Path(__file__).resolve().parents[3]
SITE = "https://www.ivankristianto.com"
FIELDS = "id,status,date,modified,link,slug,title,excerpt,content,categories,tags"


def env():
    path = REPO / ".env"
    if not path.exists():
        return {}
    pairs = (line.split("=", 1) for line in path.read_text().splitlines() if "=" in line and not line.lstrip().startswith("#"))
    return {k.strip(): v.strip().strip("'\"") for k, v in pairs}


def get(path, auth):
    headers = {"User-Agent": "Mozilla/5.0 blog-review"}
    if auth:
        headers["Authorization"] = "Basic " + base64.b64encode(auth.encode()).decode()
    req = urllib.request.Request(SITE + path, headers=headers)
    with urllib.request.urlopen(req, timeout=20) as res:
        return json.load(res)


def target(arg):
    parsed = urllib.parse.urlparse(arg)
    query = urllib.parse.parse_qs(parsed.query)
    for key in ("post", "p"):
        if query.get(key, [""])[0].isdigit():
            return "id", query[key][0]
    if arg.isdigit():
        return "id", arg
    slug = parsed.path.strip("/").split("/")[-1] if parsed.scheme else arg.strip("/")
    return "slug", slug


def to_markdown(markup):
    markup = re.sub(r"<!--.*?-->", "", markup, flags=re.S)
    markup = re.sub(r"<pre[^>]*>(.*?)</pre>", lambda m: "\n\n```\n" + re.sub(r"<[^>]+>", "", m.group(1)) + "\n```\n\n", markup, flags=re.S)
    markup = re.sub(r"<h([1-6])[^>]*>", lambda m: "\n\n" + "#" * int(m.group(1)) + " ", markup)
    markup = re.sub(r"<a [^>]*href=\"([^\"]+)\"[^>]*>(.*?)</a>", lambda m: f"[{m.group(2)}]({m.group(1)})", markup, flags=re.S)
    markup = re.sub(r"<code>(.*?)</code>", r"`\1`", markup, flags=re.S)
    markup = re.sub(r"<ol[^>]*>(.*?)</ol>", lambda m: re.sub(r"<li[^>]*>", "\n1. ", m.group(1)), markup, flags=re.S)
    markup = re.sub(r"<li[^>]*>", "\n- ", markup)
    markup = re.sub(r"</p>|<br ?/?>|</h[1-6]>", "\n\n", markup)
    markup = html.unescape(re.sub(r"<[^>]+>", "", markup))
    return re.sub(r"\n{3,}", "\n\n", markup).strip() + "\n"


def main():
    if len(sys.argv) != 3:
        sys.exit(__doc__)
    kind, value = target(sys.argv[1])
    conf = env()
    auth = f"{conf['IK2_PROD_USER']}:{conf['IK2_PROD_APP_PASSWORD']}" if conf.get("IK2_PROD_APP_PASSWORD") else ""
    context = "&context=edit" if auth else ""
    try:
        if kind == "id":
            post = get(f"/wp-json/wp/v2/posts/{value}?_fields={FIELDS}{context}", auth)
        else:
            found = get(f"/wp-json/wp/v2/posts?slug={urllib.parse.quote(value)}&status=any&_fields={FIELDS}{context}" if auth
                        else f"/wp-json/wp/v2/posts?slug={urllib.parse.quote(value)}&_fields={FIELDS}", auth)
            if not found:
                print(f"no post with slug {value!r} visible over REST" + ("" if auth else "; a draft needs Chrome or an app password in .env"), file=sys.stderr)
                sys.exit(3)
            post = found[0]
    except urllib.error.HTTPError as err:
        print(f"REST said {err.code} for {kind} {value}; " + ("check the app password" if auth else "a draft needs Chrome or an app password in .env"), file=sys.stderr)
        sys.exit(3)

    title = html.unescape(post["title"].get("raw") or post["title"]["rendered"])
    out = Path(sys.argv[2])
    out.write_text(f"# {title}\n\n" + to_markdown(post["content"]["rendered"]))
    meta = {k: post.get(k) for k in ("id", "status", "date", "modified", "link", "slug", "categories", "tags")}
    meta.update(title=title, excerpt=html.unescape(re.sub(r"<[^>]+>", "", post["excerpt"].get("raw") or post["excerpt"]["rendered"])).strip(),
                read_via="rest-auth" if auth else "rest-public",
                edit_url=f"{SITE}/wp-admin/post.php?post={post['id']}&action=edit")
    out.with_suffix(".meta.json").write_text(json.dumps(meta, indent=2))
    print(json.dumps(meta, indent=2))


if __name__ == "__main__":
    main()
