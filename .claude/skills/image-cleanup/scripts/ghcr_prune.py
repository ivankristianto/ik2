"""Prune old ik2 images from GHCR and the local Docker daemon, keeping the last N releases.

Run with `python3 -I -B` so nothing is imported from the cwd and no __pycache__ is written.
`plan` and `local` change nothing; `apply` and `local --apply` delete.
"""

import argparse, calendar, json, os, re, subprocess, sys, time, urllib.error, urllib.request

OWNER = "ivankristianto"
PACKAGES = ["ik2-app", "ik2-nginx", "ik2-cli", "ik2-embedder"]
UA = "ik2-image-cleanup/1.0"
RECENT_HOURS = 2  # buffer past the build.yml check: a tag build pushes untagged layers before the tagged index
ACCEPT = ", ".join([
    "application/vnd.oci.image.index.v1+json",
    "application/vnd.docker.distribution.manifest.list.v2+json",
    "application/vnd.oci.image.manifest.v1+json",
    "application/vnd.docker.distribution.manifest.v2+json",
])


def die(msg):
    print(f"ABORT: {msg}", file=sys.stderr)
    sys.exit(2)


def sh(args):
    return subprocess.run(args, capture_output=True, text=True)


def semver(tag):
    return tuple(map(int, tag.split("."))) if re.fullmatch(r"\d+\.\d+\.\d+", tag) else None


def versions(pkg):
    out = sh(["gh", "api", "--paginate", f"/user/packages/container/{pkg}/versions?per_page=100",
              "--jq", ".[] | {id, name, created_at, tags: .metadata.container.tags}"])
    if out.returncode:
        die(f"listing {pkg} failed: {out.stderr.strip()}")
    return [json.loads(line) for line in out.stdout.splitlines() if line.strip()]


def registry(pkg, ref, method="GET"):
    token = json.loads(urllib.request.urlopen(urllib.request.Request(
        f"https://ghcr.io/token?scope=repository:{OWNER}/{pkg}:pull", headers={"User-Agent": UA})).read())["token"]
    req = urllib.request.Request(f"https://ghcr.io/v2/{OWNER}/{pkg}/manifests/{ref}", method=method,
                                 headers={"Authorization": f"Bearer {token}", "Accept": ACCEPT, "User-Agent": UA})
    with urllib.request.urlopen(req, timeout=60) as resp:
        return json.loads(resp.read()) if method == "GET" else resp.status


def children(pkg, digest):
    return [m["digest"] for m in registry(pkg, digest).get("manifests", [])]


def dokploy_pin():
    """IMAGE_TAG on the Dokploy ik2/app compose, or None when it isn't set (prod runs latest)."""
    key, host = os.environ.get("DOKPLOY_KEY"), os.environ.get("DOKPLOY_HOST", "").rstrip("/")
    if not key or not host:
        die("DOKPLOY_KEY and DOKPLOY_HOST are not set; run through `op run` (see SKILL.md) so a pinned rollback tag is kept")
    if not host.startswith("http"):
        host = "https://" + host
    req = urllib.request.Request(f"{host}/api/project.all", headers={"x-api-key": key, "User-Agent": UA, "accept": "application/json"})
    projects = json.loads(urllib.request.urlopen(req, timeout=30).read())
    for project in projects:
        if project.get("name") != "ik2":
            continue
        for env in project.get("environments", [project]):
            for compose in env.get("compose", []):
                if compose.get("name") == "app":
                    for line in (compose.get("env") or "").splitlines():
                        name, _, value = line.partition("=")
                        if name.strip() == "IMAGE_TAG" and value.strip():
                            return value.strip().strip("'\"")
                    return None
    die("no compose named app in Dokploy project ik2; can't tell which tag prod runs")


def preflight():
    status = sh(["gh", "auth", "status"])
    scopes = status.stdout + status.stderr
    if "read:packages" not in scopes or "delete:packages" not in scopes:
        die("gh token lacks read:packages/delete:packages; run: gh auth refresh -s read:packages,delete:packages")
    runs = sh(["gh", "run", "list", "--workflow", "build.yml", "--limit", "20", "--json", "status,displayTitle"])
    if runs.returncode:
        die(f"can't check build.yml runs: {runs.stderr.strip()}")
    busy = [r["displayTitle"] for r in json.loads(runs.stdout) if r["status"] not in ("completed",)]
    if busy:
        die(f"build.yml is running ({', '.join(busy)}); its untagged layers would look orphaned. Retry when it finishes")


def keep_tags(all_tags, keep, pins):
    releases = sorted({semver(t) for t in all_tags if semver(t)}, reverse=True)[:keep]
    return {".".join(map(str, r)) for r in releases} | {"latest"} | set(pins)


def plan(keep, pins):
    now = time.time()
    result = {}
    for pkg in PACKAGES:
        vs = versions(pkg)
        wanted = keep_tags({t for v in vs for t in v["tags"]}, keep, pins)
        kept = [v for v in vs if set(v["tags"]) & wanted]
        kept_children = {d for v in kept for d in children(pkg, v["name"])}
        delete, held = [], []
        for v in vs:
            if v in kept or v["name"] in kept_children:
                continue
            age_h = (now - calendar.timegm(time.strptime(v["created_at"][:19], "%Y-%m-%dT%H:%M:%S"))) / 3600
            (held if not v["tags"] and age_h < RECENT_HOURS else delete).append(v)
        result[pkg] = {"kept": kept, "kept_children": kept_children, "delete": delete, "held": held, "wanted": wanted}
        print(f"{pkg}: {len(vs)} versions | keep {len(kept)} tagged + {len(kept_children)} children"
              f" | delete {len(delete)} | held (untagged, <{RECENT_HOURS}h) {len(held)}")
        print("   keep tags:", ", ".join(sorted(wanted, key=lambda t: semver(t) or (999,))))
        missing = wanted - {t for v in kept for t in v["tags"]}
        if missing:
            print("   note: wanted but not in this package:", ", ".join(sorted(missing)))
        tagged = sorted({t for v in delete for t in v["tags"]}, key=lambda t: semver(t) or (0,))
        if tagged:
            print("   delete tags:", ", ".join(tagged[:12]) + (f" (+{len(tagged) - 12} more)" if len(tagged) > 12 else ""))
    total = sum(len(p["delete"]) for p in result.values())
    print(f"TOTAL to delete: {total}")
    return result, total


def delete_version(pkg, vid):
    for attempt in range(5):
        out = sh(["gh", "api", "-X", "DELETE", f"/user/packages/container/{pkg}/versions/{vid}"])
        if out.returncode == 0 or "404" in out.stderr or "Not Found" in out.stderr:
            return True
        time.sleep(2 ** attempt)
    print(f"   failed {pkg} {vid}: {out.stderr.strip()[:200]}", file=sys.stderr)
    return False


def verify(keep, pins):
    broken = 0
    for pkg in PACKAGES:
        vs = versions(pkg)
        wanted = keep_tags({t for v in vs for t in v["tags"]}, keep, pins)
        for tag in sorted(wanted):
            try:
                kids = children(pkg, tag)
                for digest in kids:
                    registry(pkg, digest, method="HEAD")
                print(f"   ok {pkg}:{tag} ({len(kids)} children)")
            except urllib.error.HTTPError as e:
                broken += 1
                print(f"   BROKEN {pkg}:{tag} HTTP {e.code}")
    print(f"broken: {broken}")
    return broken


def local(keep, pins, apply):
    out = sh(["docker", "image", "ls", "--format", "{{.Repository}}:{{.Tag}}"])
    if out.returncode:
        die(f"docker isn't answering: {out.stderr.strip()}")
    refs = [r for r in out.stdout.split() if not r.endswith(":<none>") and (
        r.startswith(f"ghcr.io/{OWNER}/ik2-") or re.fullmatch(r"ik2-[\w-]+:maint", r))]
    wanted = keep_tags({r.rsplit(":", 1)[1] for r in refs}, keep, pins)
    stale = [r for r in refs if r.rsplit(":", 1)[1] not in wanted]
    for r in refs:
        print(("   remove " if r in stale else "   keep   ") + r)
    if apply and stale:
        print(sh(["docker", "rmi", *stale]).stdout.strip())
    print(f"local images to remove: {len(stale)}{' (removed)' if apply else ''}")


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("command", choices=["plan", "apply", "verify", "local"])
    ap.add_argument("--keep", type=int, default=5, help="semver releases to keep besides latest")
    ap.add_argument("--pin", action="append", default=[], help="extra tag to keep (repeatable)")
    ap.add_argument("--expect", type=int, help="apply: the TOTAL that plan printed; aborts if GHCR changed since")
    ap.add_argument("--apply", action="store_true", help="local: actually remove")
    args = ap.parse_args()

    if args.command == "local":
        return local(args.keep, args.pin, args.apply)

    pins = list(args.pin)
    if args.command != "verify":
        preflight()
    prod = dokploy_pin()
    print(f"prod IMAGE_TAG: {prod or 'unset (runs latest)'}")
    if prod:
        pins.append(prod)

    if args.command == "verify":
        sys.exit(1 if verify(args.keep, pins) else 0)

    result, total = plan(args.keep, pins)
    if args.command == "plan":
        return
    if args.expect is None or args.expect != total:
        die(f"plan now deletes {total}, --expect was {args.expect}; re-run plan and confirm again")
    failed = 0
    for pkg, p in result.items():
        for v in p["delete"]:
            failed += not delete_version(pkg, v["id"])
        print(f"{pkg}: deleted {len(p['delete'])} (failures so far {failed})")
    print(f"failures: {failed}")
    sys.exit(1 if failed else 0)


if __name__ == "__main__":
    main()
