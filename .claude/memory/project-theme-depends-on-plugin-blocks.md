---
name: project-theme-depends-on-plugin-blocks
description: ik2 theme templates hard-depend on the ik2 plugin's project-card block; deactivating the plugin silently drops content
metadata:
  type: project
---

The ik2 theme renders `ik2/project-card` in two places — `templates/single-project.html` (the feature card under the project hero) and `blocks/projects-archive/render.php` (the project grid via `do_blocks()`). That block is **not** part of the theme; it is registered by the companion **ik2 plugin** at `wp-content/plugins/ik2/blocks/project-card/`.

The shared project meta (`status`, etc.) is centralised in the plugin at `inc/post-types/project-data.php` (`IK2\Plugin\PostTypes\Project`), which the theme also calls from `patterns/single-project-header.php` and `blocks/projects-archive/helpers.php`.

**Why:** they ship together as one site, so the coupling is intentional — but it is invisible from the theme alone.

**How to apply:** if the ik2 plugin is deactivated, the single-project feature card and the projects archive render nothing (front end drops the unregistered block; the editor shows a recovery placeholder) with no error. When auditing or moving the theme, treat the plugin as a hard dependency. Verifying `ik2/project-card` escaping is the one place untrusted project meta could surface.
