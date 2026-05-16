---
name: ivankristianto-design
description: Use this skill to generate well-branded interfaces and assets for ivankristianto.com (Ivan Kristianto's personal engineering blog), either for production or throwaway prototypes/mocks/etc. Contains essential design guidelines, colors, type, fonts, assets, and UI kit components for prototyping in the "Ink, Paper, and Signal" system.
user-invocable: true
---

# Ivan Kristianto Design — "Ink, Paper, and Signal"

This skill packages the design system for **ivankristianto.com**, Ivan Kristianto's personal engineering blog. The system is restrained, technical, and reading-first — a well-kept engineering notebook. GitHub-adjacent in dark mode, warm paper in light.

## Start here

1. Read `README.md` end to end. It has the brand context, content/voice rules, visual foundations, and iconography rules.
2. Look at `colors_and_type.css` for the canonical CSS custom properties.
3. Skim `preview/` for visual specimens of every token + component.
4. Open `ui_kits/blog/index.html` for the live click-thru prototype (Home, Writing, Guides, Notes, Article, Resume).

## Mental model

- **Light first**, dark mirrors GitHub. Default to light unless asked.
- **Warm paper** (`#F8F7F3`) is the page; pure white is reserved for cards.
- **One accent**: Signal Blue `#2563EB`. Use it for links, primary CTA, focus, and tag text — nowhere else.
- **System fonts only**: `ui-sans-serif` for text, `ui-monospace` for metadata/code. Don't load webfonts.
- **Borders + whitespace do the hierarchy work.** No drop shadows except a `0 1px 2px` lift on card hover.
- **First-person, working-engineer voice.** "I use…", "Here's what I did…". No marketing speak. Sentence case. Dates in monospace.
- **Almost no iconography.** Brand/social SVGs live in `assets/icons/`; UI affordance icons use Lucide.

## What to do when invoked

- If the user asks you to build a **prototype, mock, or slide**: copy the assets you need out of this skill folder, write static HTML files that load `colors_and_type.css`, and use the patterns from `preview/` and `ui_kits/blog/`. Don't reinvent.
- If the user is working on **production code**: read the tokens from `colors_and_type.css` and lift them. `theme.json` is a drop-in for the WordPress theme.
- If the user invokes the skill with no other guidance: ask them what they want to build, ask 2–4 questions (audience, single page vs flow, dark mode?, content available?), and act as an expert designer producing either HTML artifacts or production code.

## Don'ts

- Don't add emoji to chrome.
- Don't add drop shadows beyond `--shadow-sm` on card hover and `--shadow-md` for modal/palette.
- Don't add gradients, grain, glassmorphism, or marketing-style hero imagery.
- Don't reach past Signal Blue for accent color — semantic green/amber/red are status only.
- Don't load webfonts unless asked. System fonts ship the brand identity here.
- Don't recreate iconography by hand — copy from `assets/icons/` or pull from Lucide CDN.

## File map

| Path                       | Purpose                                                 |
| :------------------------- | :------------------------------------------------------ |
| `README.md`                | Full brand + voice + visual + iconography reference     |
| `colors_and_type.css`      | All CSS custom properties + semantic element styles     |
| `theme.json`               | WordPress `theme.json` design tokens                    |
| `preview/`                 | Per-token / per-component preview cards                 |
| `ui_kits/blog/`            | Working click-thru prototype + JSX components           |
| `assets/wordmark.svg`      | Wordmark — "Ivan Kristianto." with Signal Blue period   |
| `assets/monogram.svg`      | Square monogram for favicon / nav corner                |
| `assets/icons/`            | Inline-SVG social icons (GitHub, LinkedIn, X, RSS, WP)  |
