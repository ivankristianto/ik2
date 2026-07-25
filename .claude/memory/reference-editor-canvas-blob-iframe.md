---
name: reference-editor-canvas-blob-iframe
description: Verifying WP block editor canvas state when screenshots/snapshots come back blank
metadata: 
  node_type: memory
  type: reference
  originSessionId: b1bdfb4c-1a25-47c0-bf0b-074863fee23a
  modified: 2026-07-18T04:00:17.617Z
---

The WP 7.0 block editor canvas is a `blob:` URL iframe (`name="editor-canvas"`), an out-of-process frame. agent-browser snapshots/screenshots and JS `iframe.contentDocument` can't descend into it — the canvas reads as empty/gray even when blocks render fine for a real user.

To verify canvas/block state, query the editor data store on the **main** window instead:

- `wp.data.select('core/block-editor').getBlocks()` → block list + attributes + `isValid`
- `wp.data.select('core/blocks').getBlockType('ik2/home-hero')` → registered supports/attributes
- `wp.data.select('core/editor').getEditorSettings().styles` → CSS fed into the canvas (raw, unscoped; WP applies the `.editor-styles-wrapper` prefix at iframe injection time)

To confirm a theme-CSS-into-editor-chrome leak, check `getComputedStyle(document.body).fontFamily` and a `link[rel=stylesheet]` scan on the outer document. Related: [[feedback-opcache-restart]].
