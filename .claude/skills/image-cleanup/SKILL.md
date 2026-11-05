---
name: image-cleanup
description: Delete old ik2 Docker images from GHCR (ik2-app, ik2-nginx, ik2-cli, ik2-embedder) and from the local Docker daemon, keeping latest, the last 5 releases and whatever tag prod is pinned to in Dokploy. Plans first, deletes only after Ivan confirms, then verifies every kept tag still pulls. Use when Ivan asks to clean up, prune or delete old images, GHCR packages or container versions, free registry space, "keep the last 5 releases", or types /image-cleanup. Also the optional last step of /maintenance after a release.
user-invocable: true
---

# Image cleanup: keep the last 5 releases

Every release pushes four images to GHCR (`ghcr.io/ivankristianto/ik2-{app,nginx,cli,embedder}`). Each is one tagged OCI index plus two untagged children (the amd64 image and its provenance attestation). Nothing deletes them, so the packages grow by 12 versions per release, and more for manual `workflow_dispatch` builds tagged `<branch>` and `sha-*`.

`scripts/ghcr_prune.py` does the work. Deleting a GHCR version can't be undone, so the flow is always plan, confirm, apply, verify.

## What it keeps

- `latest`.
- The newest N semver releases, 5 by default (`--keep N`).
- Prod's `IMAGE_TAG` from the Dokploy `ik2` / `app` compose. It's unset normally, which means prod runs `latest`. During a rollback it names an older release, and the script keeps it even when it falls outside the last N.
- Any `--pin TAG` you pass.
- Every untagged child of a kept index. These are read from the registry manifests, because deleting a child breaks the release that points at it.
- Untagged versions under 2 hours old. A tag build pushes those before it tags the index.

Everything else goes: older releases, `main` and `sha-*` builds, and orphaned untagged versions.

## Guards built into the script

`plan` and `apply` abort when:

- the `gh` token lacks `read:packages` or `delete:packages`. Ask Ivan to run `! gh auth refresh -s read:packages,delete:packages`.
- a `build.yml` run is queued or in progress, because its fresh layers would look orphaned. Wait for `gh run watch` to finish.
- `DOKPLOY_KEY` and `DOKPLOY_HOST` aren't set, or the Dokploy `ik2` / `app` compose can't be found, because then the script can't tell which tag prod runs.

`apply` also needs `--expect <TOTAL>` matching the total that `plan` printed. If GHCR changed between the plan and the go-ahead (for example a release landed), it aborts instead of deleting a set Ivan never saw.

## Steps

Run from the repo root. The Dokploy credentials come from 1Password through `op run`, so no value touches disk or chat:

```bash
prune() {
  DOKPLOY_KEY='op://Agents/Dokploy API/credential' DOKPLOY_HOST='op://Agents/Dokploy API/hostname' \
    op run -- python3 -I -B .claude/skills/image-cleanup/scripts/ghcr_prune.py "$@"
}
```

Define the function in the same Bash call that uses it, because shell state doesn't persist between calls. `-I -B` keeps Python from importing from the cwd or writing a `__pycache__`. An untracked `__pycache__` under `.claude/skills` blocks `scripts/release.mjs`.

1. **Plan.** `prune plan` (add `--keep N` or `--pin TAG` if Ivan asked). It lists, per package, the kept tags, the tags to delete, any held versions, and a `TOTAL`. Nothing is deleted.
2. **Confirm.** Show Ivan the plan: the kept tags, the deleted tags per package and the total. Ask before going on, every time, including when this runs as part of `/maintenance`. A `TOTAL to delete: 0` means you stop here and say so.
3. **Apply.** `prune apply --expect <TOTAL>`, with the same `--keep` and `--pin` you planned with. GitHub sometimes answers a delete with a 502 or an empty body. Each delete retries 5 times with backoff, and a 404 counts as already gone. If it ends with `failures: N` above 0, run `plan` again and repeat steps 2 and 3. The rerun plans against live GHCR, so it only picks up what's left.
4. **Verify.** `prune verify` with the same flags. It fetches every kept tag's manifest and HEADs each child. It must end with `broken: 0`. Anything else means a kept release can't be pulled. Tell Ivan right away, and don't cut a release until it's understood.
5. **Local images.** `python3 -I -B .claude/skills/image-cleanup/scripts/ghcr_prune.py local` (no `op run` needed) lists `ghcr.io/ivankristianto/ik2-*` tags outside the keep set and `ik2-*:maint` leftovers from `/maintenance`. Add `--apply` to `docker rmi` them. It never touches the dev stack (`ik2org-*`) or other projects.
6. **Build cache, only if Ivan asks.** `docker builder du` shows the size. The cache is shared with every other project on this Mac, so ask before `docker builder prune --filter until=168h`. Never run `docker image prune`, `docker system prune` or an unfiltered `builder prune` here. They hit every project.

## Report

Reply with a few lines:
- versions deleted per package and the total,
- the tags kept (and prod's `IMAGE_TAG`),
- the `verify` result,
- local images removed.

Don't put the Dokploy hostname or compose id in anything committed. This repo is public.

## Server side

The Dokploy server has its own Docker cleanup turned on, so old images on the host are pruned there. This skill doesn't log in to it.
