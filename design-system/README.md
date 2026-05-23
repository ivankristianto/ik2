# Ivan Kristianto Design System

> **Ink, Paper, and Signal** — a calm, technical, GitHub-adjacent system for a personal engineering blog.

This design system serves [ivankristianto.com](https://www.ivankristianto.com/) — the personal blog of Ivan Kristianto, a Senior Web Engineer (Human Made), Google Developer Expert in Web Technology, lead organiser of Jakarta WordPress Meetup, and WordCamp Jakarta organiser. The blog covers WordPress, web performance, security, AI, developer tooling, and Linux.

The system is intentionally **restrained**. It exists so Ivan can hit publish more often without thinking about visual decisions. Engineering notebook energy: clean, readable, technical, quiet.

---

## Index

| File / Folder         | Purpose                                                                   |
| :-------------------- | :------------------------------------------------------------------------ |
| `README.md`           | This file — context, content rules, visual foundations, iconography       |
| `SKILL.md`            | Agent Skills front-matter so this folder can be loaded as a Claude skill  |
| `colors_and_type.css` | All CSS custom properties — colors, type scale, spacing, radii, shadows   |
| `theme.json`          | WordPress `theme.json` design tokens (drop-in for the WP theme)           |
| `preview/`            | Small cards for the Design System tab — colors, type, spacing, components |
| `ui_kits/blog/`       | High-fidelity recreation of the blog (homepage, article, guides, resume)  |
| `assets/`             | Logos, favicons, generic placeholder imagery                              |
| `fonts/`              | (empty — the system uses native system fonts; no webfonts to ship)        |

### Sources of truth

- **Production theme:** `wp-content/themes/ik2/` — the active WordPress block theme and the current implementation source of truth.
- **Shared design-system copy:** `design-system/colors_and_type.css` and `design-system/theme.json` — mirrors of the production tokens for previews, prototypes, and agent use.
- **Project context:** `PRODUCT.md` and `DESIGN.md` at the repo root — current brand, tone, and token guidance.
- **Live site:** https://ivankristianto.com — content, topics, tone, post structure.

---

## Brand at a glance

| Aspect         | Value                                                                   |
| :------------- | :---------------------------------------------------------------------- |
| Author         | Ivan Kristianto                                                         |
| Role           | Senior Web Engineer, Google Developer Expert                            |
| Audience       | Working web engineers, WordPress developers, performance/security nerds |
| Topics         | WordPress · Performance · Security · AI · Linux · DevTools · JavaScript |
| Mood           | Minimal, readable, technical, calm, practical                           |
| Default theme  | Light (warm paper background; dark mode available later)                |
| Primary accent | Terracotta `#C2410C`                                                    |

---

## Content Fundamentals

The voice is **first-person, casual, practical, working-engineer**. Ivan writes the way he'd explain something to a colleague over coffee — short setup, a problem he hit, the fix, sometimes a screenshot, sometimes a code block.

### Voice rules

- **First person (I), addressing you (the reader).** "I use Cloudflare CDN…", "I would like to share couple best practices I did…", "You can see…"
- **Conversational, not corporate.** No "we are delighted to announce." No marketing scaffolding.
- **Specific, with receipts.** Real tool names, real versions, real commands, real screenshots. Code blocks > prose where possible.
- **Honest about limits.** "I'm not in the position to say it has bulletproof security." "I cannot make to Google IO 2019 this year." Owns gaps; doesn't pretend.
- **English is a second language.** Occasional small grammar slips ("a couple security has been reported"). When ghostwriting, lean naturally idiomatic — don't _imitate_ slips, just keep the register low-key.

### Casing & punctuation

- **Sentence case** for body and most UI. Title Case for post titles and primary nav.
- Tags / metadata in **lowercase**: `wordpress`, `security`, `cli`, `cloudflare`.
- Use plain straight quotes; no em-dash flourishes. The voice is engineer, not essayist.
- Code, paths, commands always in `monospace`.

### Tone examples (real, from the blog)

> "I use Cloudflare CDN to add performance and security layer to my websites for free. And most of times when I need to change some settings for the zone file, I have to login through the web dashboard, and sometimes it ask for 2FA. For just a little change I have to go through multiple steps. So I decided to create the cli tools with node.js to manage my Cloudflare account via API. And it works!"

> "Passionately Share and Learn." _(tagline)_

> "I'm a Senior Web Engineer at Human Made and Google Developer Expert in Web Technology, Lead organiser of Jakarta WordPress Meetup and WordCamp Jakarta Organiser." _(about blurb)_

### Headlines

- Direct, not clever. "Secure Your WordPress Site." "Cloudflare API CLI Tool." "Why You Should Always Use Site with HTTPS."
- Sometimes a category/event prefix: "AMP for WordPress – WordPress Jakarta #15."
- Avoid clickbait, avoid colons-as-decoration, avoid "The Ultimate Guide to…".

### Emoji and decorative characters

- **Almost none.** The brief explicitly cuts emoji and decorative gradients.
- Inline code, monospace metadata, and the terracotta signal accent carry all the "personality" the design needs.
- Acceptable: the occasional `→` arrow or `·` middle-dot as a separator. Nothing else.

### Microcopy patterns

- CTAs: verb-first, no period. **Browse Guides** · **Read Resume** · **Read more** · **Subscribe via RSS**.
- Empty states / footers: a single short sentence, lowercase or sentence case. No exclamation marks unless something genuinely deserves one.
- Dates always shown in monospace, full month + day + year ("July 8, 2020") — matches the existing site convention.

---

## Visual Foundations

The system is built on **borders + whitespace + type contrast**, not shadows, gradients, or imagery. If a design feels flat and you're tempted to add a drop shadow — resist; widen the margins instead.

### Color

- **Warm paper background** (`#F8F7F3`) — never pure white. Pure white (`#FFFFFF`) is reserved for cards and code wrappers, where it reads as "elevated surface" against the paper.
- **Ink** (`#171717`) for body text, near-black but not pure black, keeps the page from feeling harsh.
- **Terracotta** (`#C2410C`) is the single accent. It's used for links, the primary CTA, focus rings, and tag text — and nowhere else.
- A small set of **muted neutrals** (`Graphite`, `Dust`, `Line`, `Rule`) handle hierarchy without color.
- Semantic colors (`Build Green`, `Amber`, `Red`) appear only for status — never as decoration.
- Dark mode mirrors GitHub's palette (Terminal `#0D1117`, Panel `#161B22`) for engineers who live in dark mode.

### Typography

- **System fonts only.** `ui-sans-serif, system-ui` for text; `ui-monospace, SFMono-Regular` for metadata, code, and tags. No webfont download — the site loads instantly.
- **Body text is unusually large** (`1.0625rem` UI, `1.125rem` article) with generous `1.7–1.75` line-height. This is a _reading_ site first.
- **Headings are tight and confident**: `font-weight: 700`, `line-height: 1.1`, `letter-spacing: -0.04em`. They never feel like marketing.
- **Mono is used as flavor**, not as the body face: dates, tags, reading-time, eyebrows on cards, the small "WEB ENGINEER / WORDPRESS / AI / PERFORMANCE" hero label.
- Article max-width is **720px**. Wider feels like documentation; narrower feels precious.

### Spacing

- Strict **8px-based scale**, 4 → 128px in ten steps (`--space-1` through `--space-10`).
- Section padding scales with viewport using `clamp()` — hero blocks breathe on desktop without crushing mobile.
- The gap between adjacent block elements in an article is `1.25em` of the article's own font-size, so spacing scales with the type itself.

### Backgrounds

- **No imagery in the chrome.** No hero photos, no parallax, no full-bleed marketing shots. The "decoration" is the paper color and the borders.
- Section backgrounds alternate between Paper (`#F8F7F3`) and Soft Paper (`#F1EFE8`) to create rhythm without a single border line.
- Card backgrounds: Surface (`#FFFFFF`) — these are the brightest things on the page.
- No gradients. No repeating patterns. No grain. No textures.

### Borders

- Every card, panel, callout, code block: `1px solid var(--color-border)` (`#D8D5CC`). One line, no fancy colored-left-edge variants.
- Hover state on cards: border darkens to `--color-border-strong` (`#B9B5AA`) and a `0 1px 2px rgba(23,23,23,0.06)` shadow lifts it ~1px. That's the whole interaction.
- Radii are **small and consistent**: `4px` for inline code, `6px` for buttons + tags, `8px` for cards, `12px` for large panels. The pill radius (`999px`) is reserved for tags / small status pills.

### Shadows

- **Almost nothing.** `--shadow-none` is the default. `--shadow-sm` appears only on card hover. `--shadow-md` is reserved for the (future) command palette / modal.
- Spacing and borders do the hierarchy work.

### Hover, press, focus states

- **Links:** color shifts from `--color-accent` to `--color-accent-hover` (deeper terracotta). Underline thickness stays steady.
- **Cards:** border color darkens, tiny shadow appears. No transform, no scale.
- **Buttons (primary):** background swaps to the deeper terracotta. **No** translate, **no** scale, **no** shadow added on hover.
- **Buttons (secondary):** background shifts from `Surface` to `Soft Paper`; border darkens to Rule.
- **Press / active:** no special transform. The hover color persists.
- **Focus-visible:** `2px solid var(--color-accent)`, `outline-offset: 3px`. Always visible, always terracotta. This is non-negotiable — keyboard users come first, and a sharp focus ring is also a credibility signal.

### Animation

- **Use sparingly.** Transitions are `200ms ease` at most, applied to `color`, `background-color`, `border-color`, `box-shadow`.
- **No bounces, no scales, no entrances.** Content appears; it doesn't perform.
- A copy button on code blocks may briefly swap label to "Copied" — that's the most "motion" the site does.

### Transparency & blur

- Not used. The system has no glassy / frosted surfaces. The only "transparent" thing is the secondary button's hover layering on top of Soft Paper.

### Imagery

- When images appear (in posts), they're **as-is** — no filters, no duotone, no rounded-corner crops. They sit inside the article column or break out slightly to `--width-wide`.
- Hero images on post cards: simple `aspect-ratio: 16/9`, `1px solid var(--color-border)`, no shadow, no overlay.
- The site does **not** use stock photography in chrome. The post-card images are screenshots / talk photos / event photos that Ivan took.

### Layout rules

- One canonical container width (`1080px` wide / `720px` content / `1280px` full-bleed chrome).
- Header is part of the layout but **not sticky**. The production theme and prototype kit both ship it that way.
- Article pages are single-column. No sidebars. No related-posts overlay.
- Footer is full-width Soft Paper, simple link columns, RSS visible.

---

## Iconography

The site is **almost icon-free by design**. Where icons appear, they follow a strict ruleset.

### Approach

- **No icon font, no sprite, no custom icon set.** The original WordPress theme uses a small handful of social icons; the redesign moves to inline SVG.
- **Lucide** is the substituted icon set for any UI affordance that needs a glyph — copy button, RSS, external link, chevron, sun/moon for theme toggle, search. Lucide is loaded from CDN in the UI kit (`https://unpkg.com/lucide@latest`). **Substitution flag** — Ivan should confirm Lucide is acceptable; alternatives that match the engineer aesthetic equally well: Phosphor (regular weight), Heroicons (outline).
- Icons are **stroke-only, 1.5px, 20px or 24px**. No filled variants. No two-tone. No color — they inherit `currentColor` from their context (usually `--color-text-muted` or `--color-text`).
- **Brand / social icons** (GitHub, LinkedIn, Twitter/X, RSS, WordPress) ship as inline SVG in `assets/icons/` so they don't pull a network dependency for the footer.

### Emoji & unicode

- **No emoji in chrome.** Body content (article text) may contain emoji in rare cases — that's a writing choice, not a design rule.
- Unicode glyphs allowed as small text decorations: `→` (right arrow in inline read-mores), `·` (middle dot as separator in metadata: `July 8, 2020 · 4 min read · WordPress`).

### Placeholder strategy

- Where a designer would normally drop an "icon next to the heading," **drop the icon and tighten the type instead**. Borrowed iconography fights the calm engineering-notebook feel.

---

## How to use this system

1. **CSS-only projects:** import `colors_and_type.css` at the top of your stylesheet. Every token is a CSS custom property.
2. **WordPress (Gutenberg):** drop `theme.json` into the theme root. Tokens become available as `var:preset|color|paper`, `var:preset|font-size|lg`, etc.
3. **Design / mocks:** open `ui_kits/blog/index.html` for an interactive recreation. Pull JSX components from `ui_kits/blog/components/`.
4. **Agent / Claude Code:** see `SKILL.md`. The folder is a self-contained skill.

---

## Caveats

- The production theme and the prototype kit are **both real and diverged**. A change in `design-system/ui_kits/blog/` does not automatically carry into `wp-content/themes/ik2/`, and vice versa.
- The active token slug is still `signal`, but its value is **Terracotta** `#C2410C`. If you see legacy Signal Blue references, treat them as stale and sync them to the production theme.
- `wp-content/themes/ik2/theme.json` is the implementation source of truth. The copies in `design-system/` are maintained for previews, prototypes, and agent workflows, and need intentional syncing.
