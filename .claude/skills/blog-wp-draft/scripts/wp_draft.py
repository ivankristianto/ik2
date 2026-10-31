"""Create or update a WordPress draft on www.ivankristianto.com from a local blog draft, over REST.

Usage, from the repo root (credentials come from 1Password, never from a file):
  op run --env-file=.env.op.local -- python3 -I <this> preflight
  op run --env-file=.env.op.local -- python3 -I <this> push <draft.md> --report <out.json> [--apply]
  op run --env-file=.env.op.local -- python3 -I <this> seo <post-id> <seo.txt> --report <out.json> [--apply]

Without --apply both are dry runs: they read WordPress and report what would change.
seo only sets the three Yoast fields and works on any status; on a published post they go live.
op run masks "ivan" in stdout (it's part of the username), so links only go to the --report file.
"""
import base64
import difflib
import html
import json
import os
import re
import sys
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from md2blocks import SITE, DraftError, load_draft, read_seo  # noqa: E402

# IK2_WP_SITE points the script at the local stack for testing; production is the default.
SITE = os.environ.get("IK2_WP_SITE", SITE).rstrip("/")
API = SITE + "/wp-json"
UA = "Mozilla/5.0 blog-wp-draft"
YOAST = {"keyphrase": "_yoast_wpseo_focuskw", "title": "_yoast_wpseo_title", "description": "_yoast_wpseo_metadesc"}


class Fail(Exception):
    pass


def auth_header():
    user, password = os.environ.get("IK2_PROD_USER"), os.environ.get("IK2_PROD_APP_PASSWORD")
    if not user or not password:
        raise Fail("IK2_PROD_USER / IK2_PROD_APP_PASSWORD are not set; run under op run --env-file=.env.op.local")
    return "Basic " + base64.b64encode(f"{user}:{password}".encode()).decode()


def call(method, path, data=None, raw=None, headers=None):
    hdrs = {"User-Agent": UA, "Authorization": auth_header(), **(headers or {})}
    body = raw
    if data is not None:
        body = json.dumps(data).encode()
        hdrs["Content-Type"] = "application/json"
    req = urllib.request.Request(API + path, data=body, method=method, headers=hdrs)
    try:
        with urllib.request.urlopen(req, timeout=60) as res:
            return json.load(res)
    except urllib.error.HTTPError as err:
        try:
            info = json.load(err)
            detail = f"{info.get('code')}: {info.get('message')}"
        except ValueError:
            detail = err.reason
        if err.code == 401:
            detail += " (the app password in op://Agents/ik2 prod was rejected; ask Ivan to refresh it)"
        raise Fail(f"{method} {path.split('?')[0]} -> {err.code} {detail}") from None


def preflight():
    me = call("GET", "/wp/v2/users/me?context=edit&_fields=id,capabilities")
    if not me.get("capabilities", {}).get("edit_posts"):
        raise Fail("the app password works but its user can't edit posts")
    return me["id"]


def slugify(name):
    return re.sub(r"[^a-z0-9]+", "-", name.lower()).strip("-")


def feature_file(assets):
    files = [p for p in (assets / "feature.webp", assets / "feature.png") if p.exists()]
    return min(files, key=lambda p: p.stat().st_size) if files else None


def plan(draft):
    meta, blocks = load_draft(draft)
    assets = draft.with_suffix("")
    seo = read_seo(assets / "seo.txt") if (assets / "seo.txt").exists() else {}
    alt = (assets / "alt.txt").read_text().strip() if (assets / "alt.txt").exists() else ""
    image = feature_file(assets)
    warnings = []
    if not seo:
        warnings.append(f"no {assets / 'seo.txt'}; Yoast fields are left as they are")
    if image and not alt:
        raise Fail(f"{image.name} has no alt.txt beside it")
    if not image:
        warnings.append("no feature.webp or feature.png; featured image is left as it is")
    return meta, blocks, seo, alt, image, warnings


def existing_post(slug):
    fields = "id,status,link,title.raw,excerpt.raw,content.raw,categories,tags,featured_media,meta"
    found = call("GET", f"/wp/v2/posts?slug={urllib.parse.quote(slug)}&status=any&context=edit&_fields={fields}")
    if len(found) > 1:
        raise Fail(f"{len(found)} posts share slug {slug}; sort that out in wp-admin first")
    return found[0] if found else None


def resolve_terms(meta, apply, changes):
    cats = call("GET", f"/wp/v2/categories?slug={urllib.parse.quote(meta['category'])}&_fields=id")
    if not cats:
        raise Fail(f"category {meta['category']!r} doesn't exist; pick a slug from /wp/v2/categories")
    tag_ids = []
    for name in meta.get("tags", []):
        slug = slugify(name)
        found = call("GET", f"/wp/v2/tags?slug={slug}&_fields=id")
        if found:
            tag_ids.append(found[0]["id"])
            continue
        changes.append(f"create tag {name!r}")
        if apply:
            tag_ids.append(call("POST", "/wp/v2/tags", {"name": name, "slug": slug})["id"])
    return [cats[0]["id"]], tag_ids


def resolve_media(image, alt, slug, post, apply, changes):
    """Reuse the current featured image when it's the same file, so re-runs don't pile up uploads."""
    size = image.stat().st_size
    candidates = []
    if post and post.get("featured_media"):
        candidates.append(post["featured_media"])
    for found in call("GET", f"/wp/v2/media?search={urllib.parse.quote(slug)}&per_page=20&_fields=id"):
        candidates.append(found["id"])
    for media_id in dict.fromkeys(candidates):
        media = call("GET", f"/wp/v2/media/{media_id}?context=edit&_fields=id,alt_text,source_url,media_details")
        same_name = Path(urllib.parse.urlparse(media["source_url"]).path).suffix == image.suffix
        if same_name and media.get("media_details", {}).get("filesize") == size:
            if media["alt_text"] != alt:
                changes.append(f"set alt text on media {media_id}")
                if apply:
                    call("POST", f"/wp/v2/media/{media_id}", {"alt_text": alt})
            return media_id
    changes.append(f"upload {image.name} as {slug}{image.suffix}")
    if not apply:
        return None
    media = call("POST", "/wp/v2/media", raw=image.read_bytes(), headers={
        "Content-Disposition": f'attachment; filename="{slug}{image.suffix}"',
        "Content-Type": "image/webp" if image.suffix == ".webp" else "image/png"})
    call("POST", f"/wp/v2/media/{media['id']}", {"alt_text": alt})
    return media["id"]


def wanted_fields(meta, blocks, seo, cat_ids, tag_ids, media_id):
    data = {"title": meta["title"], "slug": meta["slug"], "excerpt": meta["excerpt"], "content": blocks,
            "categories": cat_ids, "tags": tag_ids}
    if media_id:
        data["featured_media"] = media_id
    if seo:
        data["meta"] = {YOAST[k]: v for k, v in seo.items()}
    return data


def diff(post, data):
    current = {"title": post["title"]["raw"], "slug": data["slug"], "excerpt": post["excerpt"]["raw"],
               "content": post["content"]["raw"], "categories": post["categories"], "tags": post["tags"],
               "featured_media": post["featured_media"], "meta": {k: post["meta"].get(k, "") for k in data.get("meta", {})}}
    changed = {k: v for k, v in data.items() if (sorted(v) if k in ("categories", "tags") else v)
               != (sorted(current[k]) if k in ("categories", "tags") else current[k])}
    return changed


def verify(post_id, data, media_id, alt):
    fields = "status,title.raw,excerpt.raw,content.raw,categories,tags,featured_media,meta,yoast_head_json.title,yoast_head_json.description"
    got = call("GET", f"/wp/v2/posts/{post_id}?context=edit&_fields={fields}")
    checks = {
        "status_draft": got["status"] == "draft",
        "content_exact": got["content"]["raw"] == data["content"],
        "title": got["title"]["raw"] == data["title"],
        "excerpt": got["excerpt"]["raw"] == data["excerpt"],
        "categories": sorted(got["categories"]) == sorted(data["categories"]),
        "tags": sorted(got["tags"]) == sorted(data["tags"]),
    }
    if media_id:
        checks["featured_media"] = got["featured_media"] == media_id
        checks["alt_text"] = call("GET", f"/wp/v2/media/{media_id}?context=edit&_fields=alt_text")["alt_text"] == alt
    for key, value in data.get("meta", {}).items():
        checks[key] = got["meta"].get(key) == value
    checks.update(yoast_head_checks(got, data.get("meta", {})))
    return checks


def yoast_head_checks(got, meta):
    head = got.get("yoast_head_json") or {}
    checks = {}
    if "_yoast_wpseo_title" in meta:
        checks["yoast_head_title"] = html.unescape(head.get("title", "")) == meta["_yoast_wpseo_title"]
    if "_yoast_wpseo_metadesc" in meta:
        checks["yoast_head_description"] = html.unescape(head.get("description", "")) == meta["_yoast_wpseo_metadesc"]
    return checks


def seo(post_id, seo_file, apply):
    fields = read_seo(seo_file)
    if not fields:
        raise Fail(f"{seo_file} has no keyphrase, title or description lines")
    preflight()
    query = "?context=edit&_fields=id,status,link,meta,yoast_head_json.title,yoast_head_json.description"
    post = call("GET", f"/wp/v2/posts/{post_id}{query}")
    wanted = {YOAST[k]: v for k, v in fields.items()}
    update = {k: v for k, v in wanted.items() if post["meta"].get(k, "") != v}
    report = {"mode": "apply" if apply else "dry-run", "post_id": post["id"], "status": post["status"],
              "live": post["status"] == "publish", "changes": [f"set {k}" for k in update],
              "before": {k: post["meta"].get(k, "") for k in wanted}}
    if apply:
        if update:
            call("POST", f"/wp/v2/posts/{post['id']}", {"meta": update})
        got = call("GET", f"/wp/v2/posts/{post['id']}{query}")
        report["checks"] = {k: got["meta"].get(k) == v for k, v in wanted.items()}
        report["checks"].update(yoast_head_checks(got, wanted))
        report["checks"]["status_unchanged"] = got["status"] == post["status"]
        report["ok"] = all(report["checks"].values())
    else:
        report["ok"] = True
    report["edit_url"] = f"{SITE}/wp-admin/post.php?post={post['id']}&action=edit"
    return report


def push(draft, apply):
    meta, blocks, seo, alt, image, warnings = plan(draft)
    user_id = preflight()
    post = existing_post(meta["slug"])
    if post and post["status"] != "draft":
        raise Fail(f"post {post['id']} with slug {meta['slug']} is {post['status']}, not a draft; this skill never touches it")
    changes = []
    cat_ids, tag_ids = resolve_terms(meta, apply, changes)
    media_id = resolve_media(image, alt, meta["slug"], post, apply, changes) if image else None
    data = wanted_fields(meta, blocks, seo, cat_ids, tag_ids, media_id)
    if post:
        update = diff(post, data)
        changes += [f"update {k}" for k in update]
        if image and not media_id:
            changes.append("update featured_media")
    else:
        update = None
        changes.append("create draft")
    report = {"mode": "apply" if apply else "dry-run", "draft": str(draft), "user_id": user_id,
              "post_id": post["id"] if post else None, "media_id": media_id, "changes": changes, "warnings": warnings}
    if apply:
        if post is None:
            created = call("POST", "/wp/v2/posts", {**data, "status": "draft"})
            report["post_id"] = created["id"]
            if media_id:
                call("POST", f"/wp/v2/media/{media_id}", {"post": created["id"]})
        elif update:
            call("POST", f"/wp/v2/posts/{post['id']}", update)
        report["media_id"] = media_id
        report["checks"] = verify(report["post_id"], data, media_id, alt)
        report["ok"] = all(report["checks"].values())
    else:
        report["ok"] = True
    if report["post_id"]:
        report["edit_url"] = f"{SITE}/wp-admin/post.php?post={report['post_id']}&action=edit"
        report["preview_url"] = f"{SITE}/?p={report['post_id']}&preview=true"
    if post and update and "content" in update:
        lines = difflib.unified_diff(post["content"]["raw"].splitlines(), blocks.splitlines(), "wordpress", "draft", lineterm="", n=0)
        report["content_diff"] = list(lines)[:80]
    return report


def main(argv):
    args = argv[1:]
    try:
        if args == ["preflight"]:
            print(f"preflight ok, user {preflight()} can edit posts")
            return 0
        if len(args) >= 4 and args[0] in ("push", "seo") and "--report" in args:
            out = Path(args[args.index("--report") + 1])
            if args[0] == "push":
                report = push(Path(args[1]), "--apply" in args)
            else:
                report = seo(int(args[1]), Path(args[2]), "--apply" in args)
            out.write_text(json.dumps(report, indent=2) + "\n")
            failed = [k for k, v in report.get("checks", {}).items() if not v]
            print(f"{report['mode']} {'ok' if report['ok'] else 'FAILED'}: post {report['post_id']}, "
                  f"{len(report['changes'])} change(s), failed checks: {failed or 'none'}; details in {out}")
            return 0 if report["ok"] else 1
    except (Fail, DraftError) as err:
        print(f"wp_draft: {err}", file=sys.stderr)
        return 2
    print(__doc__, file=sys.stderr)
    return 2


if __name__ == "__main__":
    sys.exit(main(sys.argv))
