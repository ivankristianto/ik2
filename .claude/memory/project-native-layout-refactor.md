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
- Homepage post_content (page 1882) holds the expanded pattern markup — editable in the page editor. The resume page (page 50) follows the same model: `ik2/resume-page` aggregate pattern, `page-resume.html` renders a constrained post-content block (contentSize var full) inside the `ik-resume` main, expanded markup in post_content.

**Gotchas (cost real debugging time):**

- `useRootPaddingAwareAlignments` is a TOP-LEVEL `settings` key, not `settings.spacing.*`. Misplaced it is silently ignored (body gets plain padding, no `has-global-padding`).
- `wp_update_post` with `serialize_blocks()` output MUST be wrapped in `wp_slash()` — serialize escapes `--` as `--` and unslashing strips the backslashes, which corrupts `var(--wp--*)` values in attrs (block renders but layout attrs silently drop).
- Core constrained layout gives direct children `max-width` + `margin:auto !important`, printed AFTER theme CSS — any theme cap (e.g. 720/900px flush-left prose) on a DIRECT child of a constrained group is overridden. Shield with a plain flow group in between (see page-contact.html).
- Theme.json changes need `wp cache flush` (Redis object cache holds theme.json data), on top of the [[feedback-opcache-restart]] app restart.
- Any block whose `metadata.patternName` attr survives into post_content becomes a CONTENT-ONLY LOCKED pattern instance in the editor (containers disabled, no add/remove anywhere inside). `resolve_pattern_blocks()` stamps it — the homepage regen script must strip `patternName` from metadata (keep `name` for List View labels).
- `core/image` cannot serialize a custom class on its figcaption — hand-adding one in a pattern makes the block invalid ("attempt recovery"). Style via `.parent-figure-class figcaption` instead.
- Under agent-browser the editor canvas blob iframe never loads (`contentDocument` null), so no Edit component mounts: `getBlockListSettings()` is empty and `canInsertBlockType(x, rootClientId)` is false for EVERY container. Don't misread that as a block bug — verify editing modes (`getBlockEditingMode`) and parse validity instead; appender/insertion UX needs a human browser.

See also [[reference-editor-canvas-blob-iframe]] — agent-browser CDP can't reach the canvas either (`frame @ref` falls back to main); verify editor state via `wp.data` (`getBlocks()` validity, `getSettings().__experimentalFeatures`).
