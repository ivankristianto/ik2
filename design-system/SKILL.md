---
name: ivankristianto-design
description: Use this skill to generate well-branded interfaces and assets for ivankristianto.com (Ivan Kristianto's personal engineering blog), either for production or throwaway prototypes/mocks/etc. Contains essential design guidelines, colors, type, fonts, assets, and UI kit components for prototyping in the "Ink, Paper, and Signal" system.
user-invocable: true
---

# Ivan Kristianto Design — "Ink, Paper, and Signal"

This skill packages the design system for **ivankristianto.com**, Ivan Kristianto's personal engineering blog. The system is restrained, technical, and reading-first: a well-kept engineering notebook on warm paper. Light only for now.

## Start here

1. Read `README.md` end to end. It has the brand context, content/voice rules, visual foundations, and iconography rules.
2. Tokens live in `wp-content/themes/ik2/theme.json` (presets + `settings.custom`). `tokens.css` is generated from it with the same `--wp--*` custom properties WordPress prints, and `colors_and_type.css` aliases those under short names (`--color-*`, `--space-*`) for prototypes; `DESIGN.md` at the repo root maps every group to its CSS variable.
3. Skim `preview/` for visual specimens of every token + component.
4. Open `ui_kits/blog/index.html` for the live click-thru prototype (Home, Writing, Guides, Notes, Article, Resume).

## Mental model

- **Light only.** There is no dark mode. If one is added later, it will come through `theme.json` (a style variation or settings).
- **Warm paper** (`#F8F7F3`) is the page; pure white is reserved for cards.
- **One accent**: Terracotta `#C2410C`. Use it for links, primary CTA, focus, and tag text — nowhere else.
- **System fonts only**: `ui-sans-serif` for text, `ui-monospace` for metadata/code. Don't load webfonts.
- **Borders + whitespace do the hierarchy work.** No drop shadows except `--shadow-sm` on card hover and `--shadow-md` on the command palette and modals.
- **Small radii:** `4px` (`sm`) inline code and small controls, `6px` (`md`) cards, buttons, inputs, code blocks, `8px` (`lg`) the command palette panel, `999px` pills for tags.
- **One transition:** `200ms ease` on color, background, border, and box-shadow. No transforms on hover.
- **First-person, working-engineer voice.** "I use…", "Here's what I did…". No marketing speak. Sentence case. Dates in monospace.
- **Almost no iconography.** Brand/social SVGs live in `assets/icons/`; UI affordance icons use Lucide.
- `wp-content/themes/ik2/theme.json` is the single source of truth. `theme.json` and `tokens.css` in this folder are generated from it by `pnpm tokens`; don't edit them by hand.
- The prototype kit in `ui_kits/blog/` and the production theme are diverged. Propagate intentionally.

## What to do when invoked

- If the user asks you to build a **prototype, mock, or slide**: copy the assets you need out of this skill folder, write static HTML files that load `colors_and_type.css` (keep `tokens.css` next to it; it is imported), and use the patterns from `preview/` and `ui_kits/blog/`. Don't reinvent.
- If the user is working on **production code**: reference the `theme.json` tokens, never literal values. SCSS uses the `$` aliases in `wp-content/themes/ik2/src/styles/_tokens.scss` (`$color-signal`, `$radius-md`, `$transition-base`); plain block CSS uses `var(--wp--preset--*)` and `var(--wp--custom--*)`. New tokens go into the production `theme.json` first.
- If the user invokes the skill with no other guidance: ask them what they want to build, ask 2–4 questions (audience, single page vs flow, content available?), and act as an expert designer producing either HTML artifacts or production code.

## Don'ts

- Don't add emoji to chrome.
- Don't add drop shadows beyond `--shadow-sm` on card hover and `--shadow-md` for modal/palette.
- Don't add gradients, grain, glassmorphism, or marketing-style hero imagery.
- Don't reach past Terracotta for accent color — semantic green/amber/red are status only.
- Don't load webfonts unless asked. System fonts ship the brand identity here.
- Don't recreate iconography by hand — copy from `assets/icons/` or pull from Lucide CDN.

## File map

| Path                       | Purpose                                                 |
| :------------------------- | :------------------------------------------------------ |
| `README.md`                | Full brand + voice + visual + iconography reference     |
| `tokens.css`               | Generated `--wp--*` tokens from `theme.json`            |
| `colors_and_type.css`      | Short-name aliases of `tokens.css` + element styles     |
| `theme.json`               | Copy of `wp-content/themes/ik2/theme.json`              |
| `preview/`                 | Per-token / per-component preview cards                 |
| `ui_kits/blog/`            | Working click-thru prototype + JSX components           |
| `assets/wordmark.svg`      | Wordmark: "Ivan Kristianto." with terracotta period     |
| `assets/monogram.svg`      | Square monogram for favicon / nav corner                |
| `assets/icons/`            | Inline-SVG social icons (GitHub, LinkedIn, X, RSS, WP)  |
