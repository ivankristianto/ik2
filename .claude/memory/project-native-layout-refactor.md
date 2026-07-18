---
name: project-native-layout-refactor
description: theme uses native WP layout (alignfull + constrained 1280 via custom.width.full); container-full CSS is gone; gotchas hit during the refactor
metadata:
  type: project
---

Branch `refactor/block-editor-native-layout` (July 2026) replaced the `.container-full` CSS width system with native WordPress layout. The conventions now are:

- Every template `<main>` and full-bleed section is `align:"full"` + `layout:{"type":"constrained","contentSize":"var(--wp--custom--width--full)"}` (1280). Gutters come from `settings.useRootPaddingAwareAlignments` + `styles.spacing.padding` (clamp 24→32px), giving the same 1280/1216 box as the old `.container-full`.
- Only 3 dynamic theme blocks remain: `articles-filters`, `home-featured-topics` (term grid only), `home-projects-preview` (curated grid only), plus `speaking-archive` (now takes `perPage`/`headingLevel`) and `not-found`/`projects-archive`. Everything else is core-block patterns; home guides/notes are core Query Loops with taxQuery resolved from category slug in pattern PHP.
- The /now card is `ik2/now-card` (dynamic wrapper: dot + date + foot attrs) with `ik2/now-item` InnerBlocks children (label/text RichText, `parent` locked to the card) — entries are added/removed like list items. Text attributes carry `"role": "content"` so they stay editable in content-locked contexts.
- All static pages follow the post-content model: an aggregate pattern (`ik2/home-page`, `ik2/resume-page`, `ik2/about-page`, `ik2/contact-page`, `ik2/speaking-page`) lists leaf `wp:pattern` refs; the page template renders `<main class="ik-<page>">` wrapping a `wp:post-content` block (align full, constrained, contentSize var full); the page's post_content (home 1882, about 46, contact 47, speaking 49, resume 50) holds the expanded markup, regenerated with `wp eval-file wp-content/themes/ik2/bin/regen-page-content.php` (resolves pages by path, so it works on any environment). **Deploying the post-content model to an environment requires running that script there once** — otherwise the pages render stale/empty post_content — and it must re-run whenever page patterns change. The contact aggregate keeps its plain flow shield group; the speaking aggregate carries the dynamic `ik2/speaking-archive` block in post_content, which is fine.

**Gotchas (cost real debugging time):**

- `useRootPaddingAwareAlignments` is a TOP-LEVEL `settings` key, not `settings.spacing.*`. Misplaced it is silently ignored (body gets plain padding, no `has-global-padding`).
- `wp_update_post` with `serialize_blocks()` output MUST be wrapped in `wp_slash()` — serialize escapes `--` as `--` and unslashing strips the backslashes, which corrupts `var(--wp--*)` values in attrs (block renders but layout attrs silently drop).
- Core constrained layout gives direct children `max-width` + `margin:auto !important`, printed AFTER theme CSS — any theme cap (e.g. 720/900px flush-left prose) on a DIRECT child of a constrained group is overridden. Shield with a plain flow group in between (see page-contact.html).
- Theme.json changes need `wp cache flush` (Redis object cache holds theme.json data), on top of the [[feedback-opcache-restart]] app restart.
- Any block whose `metadata.patternName` attr survives into post_content becomes a CONTENT-ONLY LOCKED pattern instance in the editor (containers disabled, no add/remove anywhere inside). `resolve_pattern_blocks()` stamps it — the homepage regen script must strip `patternName` from metadata (keep `name` for List View labels).
- `core/image` cannot serialize a custom class on its figcaption — hand-adding one in a pattern makes the block invalid ("attempt recovery"). Style via `.parent-figure-class figcaption` instead.
- WP prints layout rules (`.wp-container-* > *` from `blockGap:"0"`, `:root :where(.is-layout-*) > *` defaults) AFTER theme CSS at (0,1,0) — single-class theme margins on DIRECT children of group/post-content containers silently lose (compute to 0 or 24px). Scope margin-carrying selectors under the page root class, e.g. `.ik-resume .ik-resume__section`, to win. Fixed on the resume page (July 2026); other page partials (_about, _contact, _projects, _speaking…) likely have the same dead margins and have never rendered their authored rhythm — audit before polishing them. (About/contact/speaking now render from post-content too, so their sections sit under the same layout rules.)
- Under agent-browser the editor canvas blob iframe never loads (`contentDocument` null), so no Edit component mounts: `getBlockListSettings()` is empty and `canInsertBlockType(x, rootClientId)` is false for EVERY container. Don't misread that as a block bug — verify editing modes (`getBlockEditingMode`) and parse validity instead; appender/insertion UX needs a human browser.

See also [[reference-editor-canvas-blob-iframe]] — agent-browser CDP can't reach the canvas either (`frame @ref` falls back to main); verify editor state via `wp.data` (`getBlocks()` validity, `getSettings().__experimentalFeatures`).
