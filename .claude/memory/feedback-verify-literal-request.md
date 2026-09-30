---
name: feedback-verify-literal-request
description: Before finishing a change, re-read the user's literal ask and check the diff does exactly that (CI triggers case)
metadata:
  type: feedback
---

When the user asks for a specific behavior change, the diff must implement that exact behavior, not a nearby one. Asked to make `build.yml` run only on `workflow_dispatch` or release, I only gated the `deploy` job on tags and left `push: main` + `pull_request` triggers, so builds kept running on every main push and PR.

**Why:** The user had to discover it from CI runs and called it a stupid mistake. A partial fix that looks done costs more than no fix.

**How to apply:** Before reporting done, restate the ask as a checkable condition ("workflow triggers are exactly tags + dispatch") and grep/read the result against it. For GitHub Actions, check the `on:` block, not only job `if:` guards.
