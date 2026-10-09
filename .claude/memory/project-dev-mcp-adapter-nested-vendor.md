---
name: project-dev-mcp-adapter-nested-vendor
description: In dev, any mcp-adapter version change wipes its nested vendor/; rerun the nested composer install or the plugin bails
metadata:
  type: project
---

Dev bind-mounts host `wp-content/plugins`, so the Dockerfile's nested `composer install --working-dir=wp-content/plugins/mcp-adapter` never runs locally. When `C install`/`C update` replaces the plugin folder (any version bump), `vendor/autoload_packages.php` disappears and debug.log shows "MCP Adapter: The Composer autoloader was not found". Prod images are unaffected.

**Why:** hit during the 2026-10-09 maintenance run (0.6.1 -> 0.7.0).
**How to apply:** after bumping mcp-adapter, run the same nested install as the Dockerfile via the composer tools container before verifying the dev stack. Possible fix later: switch to `wpackagist-plugin/mcp-adapter` (on wp.org since 0.7.0). See [[project-cli-webroot-non-volume]].
