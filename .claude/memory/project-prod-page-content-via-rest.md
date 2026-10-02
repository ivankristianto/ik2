---
name: project-prod-page-content-via-rest
description: Deploys don't change pattern-backed page content on prod; update it over the REST API, never with regen-page-content.php
metadata:
  type: project
---

Pattern-backed pages (home, about, contact, speaking, resume) store their blocks in post_content, so a release ships the CSS and patterns but leaves prod content unchanged. The prod resume header has drifted from `ik2/resume-page-header` ("Updated <month> 2026", no availability badge), so `bin/regen-page-content.php` would overwrite real edits.

**Why:** 2026-10-02, the certifications section had to be added to prod by hand after v0.5.x deployed.

**How to apply:** fetch `/wp-json/wp/v2/pages?slug=<slug>&context=edit` with the application password from `.mcp.json` (`ik2` server env), splice in the serialized pattern block at a unique anchor, back up the raw content, dry-run the diff, confirm with the user, then POST. The `ik2` MCP abilities can't edit content (site info and Yoast only). See [[project-staging-sync]].
