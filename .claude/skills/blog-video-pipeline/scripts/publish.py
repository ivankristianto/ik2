"""Publish a finished blog video to every registered video host, or check what that would do.

Usage, from the repo root:
  python3 -I <this> preflight [--only youtube]
  python3 -I <this> plan <video-dir> [--only ...] [--report out.json]
  python3 -I <this> upload <video-dir> --apply [--only ...] [--reupload youtube] [--report out.json]
  python3 -I <this> readback <video-dir> [--only ...]
<video-dir> is .claude/blog/drafts/<slug>/video and holds publish.json. Commands that need credentials re-run
themselves under `op run`, with each provider's op:// references, so secrets reach only the child process.
"""
import argparse
import json
import os
import shutil
import subprocess
import sys
import urllib.request
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from providers import REGISTRY  # noqa: E402
from providers.common import UA, Fail, read_receipt  # noqa: E402

SITE = "https://www.ivankristianto.com/"
UNDER_OP = "BLOG_VIDEO_PIPELINE_UNDER_OP"


def pick(only):
    names = [n.strip() for n in only.split(",")] if only else list(REGISTRY)
    unknown = [n for n in names if n not in REGISTRY]
    if unknown:
        raise Fail(f"unknown provider(s): {', '.join(unknown)}; registered: {', '.join(REGISTRY)}")
    return [REGISTRY[n] for n in names]


def op_binary():
    shim = Path.home() / ".local/bin/op"
    return str(shim) if shim.exists() else shutil.which("op")


def ensure_credentials(providers):
    needed = {k: v for p in providers for k, v in p.CREDENTIALS.items()}
    if all(os.environ.get(k) and not os.environ[k].startswith("op://") for k in needed):
        return
    if os.environ.get(UNDER_OP):
        raise Fail(f"op run did not resolve {', '.join(needed)}; check the Agents vault items")
    op = op_binary()
    if not op:
        raise Fail("op is not on PATH")
    env = {**os.environ, **needed, UNDER_OP: "1"}
    os.execvpe(op, [op, "run", "--", sys.executable, "-I", __file__, *sys.argv[1:]], env)


def load_meta(vdir):
    path = Path(vdir) / "publish.json"
    if not path.exists():
        raise Fail(f"{path} is missing; write it first (see SKILL.md, step 4)")
    return json.loads(path.read_text())


def probe(video):
    out = subprocess.run(
        ["ffprobe", "-v", "error", "-select_streams", "v:0", "-show_entries", "stream=width,height",
         "-show_entries", "format=duration", "-of", "json", str(video)],
        capture_output=True, text=True, check=True,
    )
    data = json.loads(out.stdout)
    s = data["streams"][0]
    return {"width": s["width"], "height": s["height"], "duration": round(float(data["format"]["duration"]), 2)}


def post_status(url):
    req = urllib.request.Request(url, method="HEAD", headers={"User-Agent": UA})
    try:
        with urllib.request.urlopen(req, timeout=30) as res:
            return res.status
    except Exception as err:  # noqa: BLE001
        return getattr(err, "code", str(err))


def check_common(meta, vdir, online=True):
    """Problems every provider shares: files present, the post live and named in the description."""
    vdir = Path(vdir)
    problems, facts = [], {}
    slug = meta.get("slug", "")
    url = SITE + slug + "/"
    if meta.get("url") != url:
        problems.append(f"url should be {url}")
    if url not in meta.get("description", ""):
        problems.append("description must contain the post URL")
    for key in ("video", "cover"):
        if not (vdir / meta.get(key, "")).is_file():
            problems.append(f"{key} file not found: {vdir / meta.get(key, '')}")
    if (vdir / meta.get("video", "")).is_file():
        facts["video"] = probe(vdir / meta["video"])
        v = facts["video"]
        vertical = v["height"] > v["width"]
        if vertical != (meta.get("orientation", "vertical") == "vertical"):
            problems.append(f"orientation is {meta.get('orientation', 'vertical')} but the video is {v['width']}x{v['height']}")
        if v["duration"] > 60:
            problems.append(f"video runs {v['duration']} s; blog-video keeps it under a minute")
    if online:
        facts["postStatus"] = post_status(url)
        if facts["postStatus"] != 200:
            problems.append(f"{url} returned {facts['postStatus']}, not 200")
    return problems, facts


def emit(result, report):
    text = json.dumps(result, indent=2)
    if report:
        Path(report).parent.mkdir(parents=True, exist_ok=True)
        Path(report).write_text(text + "\n")
    print(text)


def main(argv=None):
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("command", choices=("preflight", "plan", "upload", "readback"))
    ap.add_argument("vdir", nargs="?")
    ap.add_argument("--only")
    ap.add_argument("--report")
    ap.add_argument("--apply", action="store_true")
    ap.add_argument("--reupload", default="", help="comma-separated providers to upload again despite a receipt")
    args = ap.parse_args(argv)
    providers = pick(args.only)

    if args.command != "preflight" and not args.vdir:
        raise Fail(f"{args.command} needs the video folder")
    if args.command == "upload" and not args.apply:
        raise Fail("upload needs --apply; run plan for a dry run")

    if args.command == "preflight":
        ensure_credentials(providers)
        result = {}
        for p in providers:
            try:
                result[p.NAME] = {"ok": True, **p.preflight()}
            except Fail as err:
                result[p.NAME] = {"ok": False, "error": str(err)}
        emit(result, args.report)
        return 0 if all(r["ok"] for r in result.values()) else 1

    meta = load_meta(args.vdir)

    if args.command == "plan":
        problems, facts = check_common(meta, args.vdir)
        result = {"problems": problems, "facts": facts, "providers": {p.NAME: p.plan(meta, args.vdir) for p in providers}}
        emit(result, args.report)
        blocked = problems or any(r["problems"] for r in result["providers"].values())
        return 1 if blocked else 0

    if args.command == "upload":
        problems, _ = check_common(meta, args.vdir)
        if problems:
            raise Fail("; ".join(problems))
        ensure_credentials(providers)
        reupload = {n.strip() for n in args.reupload.split(",") if n.strip()}
        result = {}
        for p in providers:
            try:
                result[p.NAME] = {"ok": True, **p.upload(meta, args.vdir, reupload=p.NAME in reupload)}
            except Fail as err:
                result[p.NAME] = {"ok": False, "error": str(err)}
        emit(result, args.report)
        return 0 if all(r["ok"] for r in result.values()) else 1

    ensure_credentials(providers)
    result = {}
    for p in providers:
        receipt = read_receipt(args.vdir, p.NAME)
        result[p.NAME] = p.readback(receipt, meta) if receipt else {"error": "no receipt; not uploaded yet"}
    emit(result, args.report)
    return 0


if __name__ == "__main__":
    try:
        sys.exit(main())
    except Fail as err:
        print(f"error: {err}", file=sys.stderr)
        sys.exit(1)
