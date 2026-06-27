---
name: project-no-worktrees-docker-bindmount
description: Don't use git worktrees for work that needs container verification; the Docker stack bind-mounts the main checkout, so use a feature branch in place
metadata:
  type: project
---

The dev stack (`docker-compose.yml`) bind-mounts paths from **this** checkout into the running containers — `./wp-content/themes`, `./wp-content/plugins`, `./wp-content/mu-plugins`, `./vendor`, and the `uploads` volume — into both the `app` and persistent `wp-cli` services. `composer dev:wp:cmd -- <cmd>` runs WP-CLI inside that `wp-cli` container.

**Why it matters:** a `git worktree` created elsewhere is NOT seen by the running containers — they still mount the main working directory. So any change made in a worktree won't be exercised by `composer dev:wp:cmd`, `composer dev`, or the browser. Verification silently runs against the main checkout's code, not your worktree.

**How to apply:** for any feature that needs to be verified by running the app/CLI (most theme/plugin work), isolate with a **feature branch checked out in the main working directory**, not a worktree. Checking out a branch changes the bind-mounted files in place, which the containers pick up. Reserve worktrees for pure off-stack work (docs, analysis) only. When running subagent-driven-development or using-git-worktrees here, override the worktree step with branch-in-place.

Related: opcache means new PHP files / new `require_once` need `docker compose restart app wp-cli` before they load — see [[feedback-opcache-restart]].
