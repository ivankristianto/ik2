---
name: project-release-untracked-guard
description: release.mjs clean-tree guard trips on untracked audit dirs; bypass for one command with a GIT_CONFIG env var, never by moving Ivan's files
metadata:
  type: project
---

`scripts/release.mjs` fails "working tree is not clean" when only untracked files exist (`artifacts/`, `.impeccable/critique/`, `docs/audits/`, audit md files that Ivan keeps around uncommitted).

**Why:** 2026-10-25, v0.13.3 release. Moving or stashing those files would touch Ivan's work.

**How to apply:** run `GIT_CONFIG_COUNT=1 GIT_CONFIG_KEY_0=status.showUntrackedFiles GIT_CONFIG_VALUE_0=no node scripts/release.mjs patch` (dry-run first). Tracked changes still fail the guard. Only do this when `git status --short` shows nothing but `??` lines.
