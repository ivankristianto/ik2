# Lab tool guidelines

How a tool in the lab repo (`ik2-lab`) has to look and behave so that, framed on its entry at `/lab/<slug>/`, it reads as part of the page. The technical contract (headers, origin, the resize message) is in `docs/specs/2026-11-11-lab-section-design.md`. This document covers the brand side.

The shared stylesheet that implements these rules is `shared/lab.css` in the lab repo. It reads `shared/tokens.css`, a copy of `design-system/tokens.css` from a tagged ik2 release. A tool links both and writes no colour, size or spacing value of its own.

## The frame it sits in

The entry page draws one bordered Surface sheet, as wide as the article text (60rem). On top is a mono bar with the tool's address, a `source` link and a `new tab` link. The tool fills the rest.

- The page owns the title, the lead, the date and the links. A framed tool shows none of them.
- The sheet's border and radius belong to the page. A tool draws no outer border, frame or shadow around itself.
- Under 40em of viewport the sheet runs edge to edge, so the tool's own side padding is the page gutter.

## Two modes

A tool sets `data-embedded` on `<html>` before first paint when `window.top !== window`:

```html
<script>if (window.top !== window) document.documentElement.setAttribute('data-embedded', '');</script>
```

| | Framed (`data-embedded`) | Opened on its own |
| --- | --- | --- |
| Background | Surface, to match the sheet | Paper, like the site |
| Body padding | spacing 5 (24px) all round | spacing 6 top and bottom, spacing 5 at the sides |
| Header | none | `.lab-standalone`: the tool name as an `h1` and a mono link back to its entry |

## Type

- System sans for everything a visitor reads, system mono for labels, data and code. No webfont.
- Body text is the `small` preset (16 to 17px). The presets are fluid on viewport width, and inside the frame the viewport is the frame, so text there computes up to 0.3px smaller than the same preset on the page (16.7px against 17px at a 1440px window). That is below what the eye picks up; don't correct for it with hard-coded sizes.
- Labels are mono `x-small` in Graphite, lowercase: `sample text`, `result`, `file`.
- The largest heading inside a framed tool is `medium`. The entry already has the `h1`.
- Nothing a visitor reads is below `x-small` (14px).
- Form controls are 16px on phones. Smaller text makes iOS zoom the page on focus.

## Colour

- One accent, Bookcloth (`signal`): links, the focus ring, the caret and text selection. Keep it rare.
- Ink for text and the primary button. Graphite for labels and secondary text. Dust for hints, counts and table headers.
- Line for every resting border, Rule on hover.
- Code Paper behind anything pasted or typed as code or data.
- Build Green, Amber and Red mean status only: a result passed, needs attention, or failed. Never decoration, never a category.
- Light only. No `prefers-color-scheme` block.

## Parts

Use the classes in `lab.css`. Add a part there, not in a tool, when a second tool needs it.

| Part | Class | Rule |
| --- | --- | --- |
| Field | `.lab-field`, `.lab-label`, `.lab-input`, `.lab-textarea`, `.lab-select`, `.lab-hint` | Label above the control, in mono. Surface fill, 1px Line border, medium radius, 44px tall or more. `--code` variants sit on Code Paper in mono. |
| Button | `.lab-button`, `.lab-button--secondary` | Primary is Ink with Paper text and turns Deep Bookcloth on hover. Secondary is Surface with a Line border. Medium radius, never a pill, 44px tall. One primary per view. Verb first: **Inspect**, **Fetch**, **Copy**. |
| Output | `.lab-output`, `.lab-output__head` | A mono head line over a 1px Ink rule, the name on the left and a count or note on the right in Dust. |
| Table | `.lab-table` | Mono, lowercase headers in Dust, horizontal rules only, numbers right-aligned with `tabular-nums`. Headers never wrap. |
| Status | `.lab-status`, `--ok`, `--warn`, `--error` | A dot and a lowercase mono word. The word carries the meaning, the colour supports it. |
| Error | `.lab-error` | 1px Red border, Ink text. Say what went wrong and what to do next. |
| Empty | `.lab-empty` | One Graphite sentence that says what to paste or press. |
| Layout | `.lab-stack`, `.lab-split` | Input on the left, output on the right from 45em of frame width, stacked below that. |

## Behaviour

- **Height.** Report `document.body`'s height through a `ResizeObserver` with the `ik2-lab:resize` message. Never scroll inside the frame; the page scrolls.
- **First paint.** Render a useful default result on load, so the frame is never an empty form.
- **Links.** Every link opens a new tab (`target="_blank" rel="noopener"`). The sandbox blocks top navigation.
- **Focus.** Keep the ring from `lab.css`: 2px Bookcloth, 3px offset, on every control.
- **Motion.** `200ms ease` on colour, background and border only. No transforms, no entrance animation.
- **Privacy line.** If input stays in the browser, say so in a `.lab-hint` under the field: "Nothing leaves your browser." If the tool calls a server, say that.
- **Copy.** First person, plain, no exclamation marks, no em dashes. Labels are lowercase. Errors are sentences.

## Don'ts

- No shadow, gradient, glass effect or icon font.
- No emoji in controls or labels.
- No second accent colour and no coloured side stripe.
- No modal. Show results in place.
- No title, logo or footer inside a framed tool.

## Checking a tool

1. Open it on its own at phone and desktop widths, then framed on its entry.
2. Compare a label and a line of body text inside the frame with the page's mono meta line and small text. They should match in size and colour.
3. Tab through the page. Focus enters the tool after the bar's links and leaves to the write-up.
4. Stop the lab host and reload the entry. After eight seconds the sheet shows "The tool did not load."
5. Set the entry's two starting heights to the tool's height on first paint at desktop and phone widths, and confirm the write-up doesn't move.
