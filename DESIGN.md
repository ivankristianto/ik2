---
name: Ivan Kristianto — Ink, Paper, and Signal
description: Calm, technical, reading-first design system for a personal engineering blog. Borders and whitespace do the hierarchy work.
colors:
  paper: "#F8F7F3"
  surface: "#FFFFFF"
  soft-paper: "#F1EFE8"
  ink: "#171717"
  graphite: "#5F6368"
  dust: "#676D79"
  line: "#D8D5CC"
  rule: "#B9B5AA"
  signal: "#C2410C"
  signal-deep: "#9A3412"
  signal-soft: "#FFEDD5"
  code-paper: "#EFEEE8"
  code-ink: "#111827"
  build-green: "#15803D"
  amber: "#B45309"
  red: "#B91C1C"
typography:
  display:
    fontFamily: "ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif"
    fontSize: "clamp(2.5rem, 6vw, 4.5rem)" # preset display
    fontWeight: 700 # custom.fontWeight.bold
    lineHeight: 1.1 # custom.lineHeight.tight
    letterSpacing: "-0.04em" # custom.letterSpacing.tightest
  headline:
    fontFamily: "ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif"
    fontSize: "clamp(2rem, 5vw, 3rem)" # preset headline
    fontWeight: 700 # custom.fontWeight.bold
    lineHeight: 1.1 # custom.lineHeight.tight
    letterSpacing: "-0.04em" # custom.letterSpacing.tightest
  title-lg:
    fontFamily: "ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif"
    fontSize: "clamp(1.75rem, 4vw, 2.5rem)" # preset title-lg
    fontWeight: 700 # custom.fontWeight.bold
    lineHeight: 1.1 # custom.lineHeight.tight
    letterSpacing: "-0.03em" # custom.letterSpacing.tighter
  subtitle:
    fontFamily: "ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif"
    fontSize: "clamp(1.375rem, 2.4vw, 1.625rem)" # preset subtitle
    fontWeight: 700 # custom.fontWeight.bold
    lineHeight: 1.25 # custom.lineHeight.snug
    letterSpacing: "-0.02em" # custom.letterSpacing.tight
  card-title:
    fontFamily: "ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif"
    fontSize: "1.25rem" # preset xl
    fontWeight: 700 # custom.fontWeight.bold
    lineHeight: 1.25 # custom.lineHeight.snug
    letterSpacing: "-0.02em" # custom.letterSpacing.tight
  body:
    fontFamily: "ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif"
    fontSize: "1.125rem" # preset lg
    fontWeight: 400 # custom.fontWeight.regular
    lineHeight: 1.7 # custom.lineHeight.relaxed
    letterSpacing: "0" # custom.letterSpacing.normal
  label:
    fontFamily: "ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, Liberation Mono, monospace"
    fontSize: "0.875rem" # preset sm
    fontWeight: 500 # custom.fontWeight.medium
    lineHeight: 1.4 # custom.lineHeight.normal
    letterSpacing: "0.02em" # custom.letterSpacing.wide
rounded:
  sm: "4px"
  md: "6px"
  lg: "8px"
  pill: "999px"
spacing:
  "1": "4px"
  "2": "8px"
  "3": "12px"
  "4": "16px"
  "5": "24px"
  "6": "32px"
  "7": "48px"
  "8": "64px"
  "9": "96px"
  "10": "128px"
components:
  button-primary:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.paper}"
    rounded: "{rounded.md}"
    padding: "12px 16px"
  button-primary-hover:
    backgroundColor: "{colors.signal-deep}"
    textColor: "{colors.paper}"
    rounded: "{rounded.md}"
    padding: "12px 16px"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "12px 16px"
  button-secondary-hover:
    backgroundColor: "{colors.soft-paper}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "12px 16px"
  card:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "24px"
  card-hover:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "24px"
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "10px 12px"
  tag:
    backgroundColor: "{colors.soft-paper}"
    textColor: "{colors.graphite}"
    rounded: "{rounded.pill}"
    padding: "2px 8px"
    typography: "{typography.label}"
  tag-hover:
    backgroundColor: "{colors.signal-soft}"
    textColor: "{colors.signal-deep}"
    rounded: "{rounded.pill}"
    padding: "2px 8px"
    typography: "{typography.label}"
  nav-link:
    backgroundColor: "transparent"
    textColor: "{colors.ink}"
    padding: "8px 0"
    typography: "{typography.body}"
---

# Design System: Ivan Kristianto — Ink, Paper, and Signal

## 1. Overview

**Creative North Star: "The Engineering Notebook on Warm Paper"**

This is the design of a well-kept lab notebook. Open it and the page is warm paper, the ink is near-black, and the only color you see is the occasional terracotta mark in the margin — a link the writer wants you to follow, a tag for a topic, a focus ring that won't let you lose your place. Everything else is type, line, and whitespace doing the hierarchy work. The personality comes from the contrast between the system sans body and the system mono metadata: the page is for reading, the metadata is for scanning, and the two never get confused.

The system rejects the marketing register entirely. It does not look like a Medium template, a Substack newsletter, a SaaS landing page, or an AI tool launch. It does not animate on scroll, it does not bounce on hover, it does not have a hero image. It is the design of someone who already has a job and is publishing because they have something to say — not someone trying to convert you. When in doubt, widen the margins instead of adding a shadow; tighten the type instead of adding an icon.

**Key Characteristics:**

- Warm paper background `#F8F7F3` — never pure white.
- One accent: Terracotta `#C2410C`, used on ≤10% of any screen.
- System fonts only; no webfont download.
- Borders + whitespace + type contrast carry hierarchy. No gradients, no glassmorphism, no decorative shadows.
- Mono is the signal: dates, tags, metadata, eyebrows, code.
- Light only for now. If a dark mode comes later, it will be added through `theme.json` (a style variation or settings), not hand-written CSS.

## 2. Colors

A restrained palette of warm neutrals plus one terracotta accent. Status colors exist but only for status, never for decoration.

### Primary

- **Terracotta** (`#C2410C`): the single accent. Links, primary CTA hover, focus rings, active filter pills, tag-on-hover. The "one voice" of the system. Used sparingly — its rarity is what makes it read as signal.
- **Deep Terracotta** (`#9A3412`): hover state for the accent. Also used as text color on active filter pills against Terracotta Wash backgrounds.
- **Terracotta Wash** (`#FFEDD5`): pale accent background for active states (selected tags, active pagination, command palette active row). Never used as a decorative fill.

### Neutral

- **Paper** (`#F8F7F3`): the page background. Warm off-white. Never `#FFFFFF` for the page.
- **Surface** (`#FFFFFF`): card and code-wrapper background. Reads as "elevated surface" against Paper.
- **Soft Paper** (`#F1EFE8`): footer, alternating section backgrounds, secondary button hover, default tag background. Creates rhythm without drawing a line.
- **Ink** (`#171717`): main body and heading text. Near-black, not pure black, so the page never feels harsh.
- **Graphite** (`#5F6368`): metadata, descriptions, secondary copy.
- **Dust** (`#676D79`): dates, tag labels at rest, tertiary labels. Clears WCAG AA (4.85:1) on Paper.
- **Line** (`#D8D5CC`): default borders on cards, inputs, code blocks, dividers.
- **Rule** (`#B9B5AA`): stronger dividers and the hover-state border on cards.
- **Code Paper** (`#EFEEE8`): background for inline code and `<pre>` blocks.
- **Code Ink** (`#111827`): code text color.

### Status (use only for status)

- **Build Green** (`#15803D`): success, active build, "shipped" badges.
- **Amber** (`#B45309`): warnings, outdated notes.
- **Red** (`#B91C1C`): errors only.

### Named Rules

**The One Voice Rule.** Terracotta is used on no more than 10% of any given screen. If it starts to look like a color theme rather than a signal, it has lost its meaning. Replace decorative uses with weight, scale, or whitespace.

**The No Pure White Rule.** The page background is Paper `#F8F7F3`, never `#FFFFFF`. Pure white is reserved for cards and code wrappers, where it reads as elevation against the warm page.

**The Status-Only Rule.** Build Green, Amber, and Red are reserved for status. They never appear as decoration, category colors, or accent fills. If a category needs visual differentiation, use a tint of Paper, not a status color.

## 3. Typography

**Display Font:** `ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif`
**Body Font:** same system sans stack
**Label / Mono Font:** `ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace`

**Character:** the page voice is the operating system's own voice — whatever the reader's OS thinks "default sans" looks like. The mono stack carries the engineering signal: dates, tags, metadata, eyebrows, code. The contrast between sans body and mono metadata is the entire visual personality of the system. No webfonts ship.

### Hierarchy

Sizes are `theme.json` font-size presets (slug in backticks). Line heights and tracking come from `settings.custom.lineHeight` and `settings.custom.letterSpacing`.

- **Display** (700, `display` `clamp(2.5rem, 6vw, 4.5rem)`, tight 1.1, tightest -0.04em): the home hero title on desktop, and only there. On phones the hero switches to the `hero` tier `clamp(3rem, 8vw, 5.5rem)`.
- **Headline** (700, `headline` `clamp(2rem, 5vw, 3rem)`, tight 1.1, tightest -0.04em): page titles (archive headers, single-article H1).
- **Title LG** (700, `title-lg` `clamp(1.75rem, 4vw, 2.5rem)`, tight 1.1, tighter -0.03em): H2 inside articles.
- **Subtitle** (700, `subtitle` `clamp(1.375rem, 2.4vw, 1.625rem)`, snug 1.25, tight -0.02em): H3 inside articles.
- **Card title** (700, `xl` `1.25rem`, snug 1.25, tight -0.02em): article card titles.
- **Body** (400, `lg` `1.125rem`, relaxed 1.7, normal tracking): the page default and article prose. UI copy such as the hero blurb drops to `md-plus` `1.0625rem`. Article max line length is 720px / ~70ch.
- **Label** (500, `sm` `0.875rem`, mono, normal 1.4, wide 0.02em): tags, dates, reading time, eyebrows on cards, the small "WEB ENGINEER · WORDPRESS · AI · PERFORMANCE" hero label. Lowercase or sentence case; uppercase only for true labels.

### Named Rules

**The Mono-as-Signal Rule.** Mono is not the body face. It is reserved for things that are *not* prose: dates, tags, reading-time, file paths, code, command-palette UI, eyebrow labels on cards. If a paragraph is in mono, it has lost its meaning.

**The 720 Rule.** Article content caps at `720px` wide. Wider feels like documentation; narrower feels precious. The container around chrome (header, footer, archive grid) widens to `1080px`, and full-bleed sections to `1280px`, but reading column stays 720.

**The Tight Headline Rule.** Headings carry `font-weight: 700`, `line-height: 1.1`, and `letter-spacing: -0.04em` (loosening slightly at smaller sizes). They are confident and tight; they never feel like marketing.

## 4. Elevation

The system is **flat by default**. Depth is conveyed through borders (`1px solid var(--color-line)`), warm-paper-to-white surface change (Paper → Surface), and alternating section backgrounds (Paper ↔ Soft Paper). Shadows appear only as a quiet response to state — never as ambient decoration.

### Shadow Vocabulary

- **`--shadow-sm`** (`0 1px 2px rgba(0, 0, 0, 0.04), 0 1px 1px rgba(0, 0, 0, 0.03)`): card hover. A 1px lift, nothing more. The hover state also shifts the border from Line to Rule; the shadow is the smaller half of the gesture.
- **`--shadow-md`** (`0 6px 24px rgba(0, 0, 0, 0.08)`): reserved for the command palette and (future) modal surfaces. The only place the system permits a real shadow.

### Named Rules

**The Flat-By-Default Rule.** Surfaces are flat at rest. The first instinct when something looks "missing" is *not* to add a shadow. Try widening margins, increasing type contrast, or adding a `1px` border first. If those don't work, the layout is wrong, not the elevation.

**The Border-as-Elevation Rule.** A `1px solid #D8D5CC` border conveys card-ness more honestly than a `box-shadow`. The shadow is the lift on interaction; the border is the object.

**The 2014-App Test.** If a card looks like a 2014 iOS app — large soft shadow, big blur radius, low offset — the shadow is wrong for this system. Cut it.

## 5. Components

Each component leads with the feel, then the spec. All values reference tokens. No hardcoded hex or px elsewhere in the codebase.

### Buttons

- **Shape:** `6px` radius (`md`). Pills are reserved for tags.
- **Primary:** Ink background `#171717`, Paper text `#F8F7F3`, `12px 16px` padding. Hover: background swaps to Deep Terracotta `#9A3412`. No transform, no scale, no added shadow. Transition: `200ms ease` (`transition.base`) on `color` and `background-color`.
- **Secondary:** Surface background `#FFFFFF`, Ink text, `1px solid #D8D5CC` border. Hover: background to Soft Paper `#F1EFE8`, border to Rule `#B9B5AA`. Same transition; no shadow added.
- **Hero / Inline:** the home hero uses a "underline-grow" treatment instead of a button: text in Ink at title scale with a `linear-gradient(Terracotta, Terracotta)` painted as a 2px-thick `background-size: 100% 2px` underline that grows to 4px on hover. The link feels like a CTA without looking like one.
- **Focus:** `2px solid #C2410C`, `outline-offset: 3px`. Always visible, always Terracotta. Non-negotiable.

### Chips / Tags

- **Style:** Soft Paper background `#F1EFE8`, Graphite text, `1px solid #D8D5CC` border, pill radius (`999px`), `2px 8px` padding, mono `0.75rem`.
- **Hover / Active:** background to Terracotta Wash `#FFEDD5`, border to Terracotta, text to Deep Terracotta. The same treatment marks the current page in pagination.
- **Casing:** always lowercase (`wordpress`, `security`, `cli`).

### Cards

- **Corner Style:** `6px` radius (`md`).
- **Background:** Surface `#FFFFFF`. Article-card covers use Soft Paper as a fallback, with optional category-tinted backgrounds (`#E0E7FF` WordPress, `#FDE68A` AI, `#D1FAE5` performance, `#FECACA` security, etc.) applied via `:has()` selectors from `custom.color.categoryTint`. Tints, never saturated colors.
- **Border:** `1px solid #D8D5CC` at rest.
- **Shadow Strategy:** none at rest. On hover: border darkens to Rule `#B9B5AA` and `--shadow-sm` adds a 1px lift. That is the entire interaction. No transform, no scale.
- **Internal Padding:** `24px` (`--space-5`). Article-card content uses `12–16px` rhythm between meta, title, excerpt, tags.

### Inputs / Fields

- **Style:** Surface background, Ink text, `1px solid #D8D5CC` border, `6px` radius (`md`), `10px 12px` padding, body sans `1.0625rem`.
- **Focus:** the global `:focus-visible` rule applies: `2px solid Terracotta`, `outline-offset: 3px`. Border itself does not change color on focus; the outline ring carries the affordance.
- **Disabled:** Dust text on Soft Paper background; border stays Line.

### Navigation

- **Style:** plain text links in Ink, body sans, no underline at rest. Active page: underline in Terracotta (text-decoration-thickness `0.08em`, offset `0.18em`). Hover: color to Terracotta.
- **Mono separator:** middle dot `·` in Dust between nav items where the layout calls for it.
- **No sticky:** the header does not stick. The brief is explicit: ships non-sticky.
- **Mobile:** the same horizontal layout collapses; no hamburger menu unless the count exceeds five items.

### Code Blocks

- **Inline code:** Code Paper background `#EFEEE8`, Code Ink text, `1px solid #D8D5CC`, `4px` radius (`sm`), `0.125rem 0.35rem` padding, mono `0.9375em`.
- **`<pre>` blocks:** same colors and border, `6px` radius (`md`), `24px` padding, mono `0.9375rem` (`sm-plus`), line-height 1.7 (`relaxed`). Horizontal scroll allowed; vertical never (let the code be tall).
- **Copy button:** the only "performance" the site does. Pressing it briefly swaps the label to "Copied". No icon required; the label is enough.

### Command Palette (signature component)

- **Overlay:** `custom.color.scrim` (Ink at 30%) with a `2px` backdrop-blur. This is the one permitted use of `backdrop-filter` in the entire system, justified because the palette must read as a modal.
- **Panel:** Surface background, `1px solid Line`, `8px` radius (`lg`), `--shadow-md`, max-width 640px.
- **Active row:** Terracotta Wash background, Deep Terracotta text — same treatment as active tag/pagination.
- **Type:** mono throughout for the palette's own UI (group titles, escape hint, footer keybinds); sans for the labels of the items themselves.

## 6. Do's and Don'ts

### Do:

- **Do** use Terracotta `#C2410C` for links, primary CTA, focus rings, and active states — and nowhere else.
- **Do** keep Terracotta on ≤10% of any given screen. If it spreads, it stops meaning anything.
- **Do** use the warm paper `#F8F7F3` as the page background. Reserve pure white `#FFFFFF` for cards and code wrappers.
- **Do** use mono (system mono stack) for dates, tags, reading-time, eyebrows, file paths, and code — *not* for body prose.
- **Do** cap article content at `720px` wide; chrome at `1080px`; full-bleed sections at `1280px`.
- **Do** apply `:focus-visible` as `2px solid Terracotta, outline-offset: 3px` on every interactive element. Always.
- **Do** lift cards on hover with a border-color change (Line → Rule) plus `--shadow-sm`. No transform, no scale.
- **Do** transition `color`, `background-color`, `border-color`, and `box-shadow` at `200ms ease` (`transition.base`). Nothing else. The one exception is the mobile nav drawer slide, which uses `transition.drawer`.
- **Do** widen margins or tighten type when a layout feels empty. Reach for whitespace before reaching for a shadow or an icon.
- **Do** keep CTAs verb-first with no period: **Browse Articles**, **Read more**, **Subscribe via RSS**.
- **Do** show dates in monospace, full format: `July 8, 2020`.

### Don't:

- **Don't** reintroduce the legacy Signal Blue `#2563EB` accent. The active accent is Terracotta `#C2410C`. The token slug is `signal` for stability; the value moved.
- **Don't** add a webfont, `@font-face`, or Google Fonts link. System fonts only.
- **Don't** add shadows beyond `--shadow-sm` on card hover and `--shadow-md` on the command palette and modals. No ambient `box-shadow: 0 4px 20px rgba(0,0,0,0.1)` on cards at rest.
- **Don't** use gradients anywhere except the hero "underline-grow" CTA treatment (a solid-color `linear-gradient(Terracotta, Terracotta)` used as a sized background, not as decoration).
- **Don't** use glassmorphism, frosted blur, grain textures, or noise overlays. The one permitted `backdrop-filter` is the command-palette overlay.
- **Don't** apply `border-left: 4px solid` (or any > 1px colored side-stripe) on cards, callouts, list items, or alerts. Use a full `1px` border, a background tint, or a leading mono label instead.
- **Don't** use gradient text (`background-clip: text` with a gradient). Use a single solid color. Emphasis via weight or scale.
- **Don't** copy the SaaS hero-metric template (big number, small label, gradient accent, supporting stats). The site has no metrics page.
- **Don't** lay out identical card grids with icon + heading + text repeated endlessly. Vary the rhythm — article cards have covers, guide cards have leading numbers, notes have date-led layout.
- **Don't** reach for a modal as the first thought. Inline disclosure, expanded section, or a new page first.
- **Don't** animate layout properties (`width`, `height`, `top`, `left`, `margin`). No bounces, no scales, no entrance animations, no scroll-driven parallax.
- **Don't** add emoji to chrome (nav, footer, buttons, headings, eyebrows). Body content is the writer's call.
- **Don't** use em dashes in copy. Commas, colons, semicolons, periods, parentheses do the work.
- **Don't** add an icon next to a heading "to make it feel designed." Tighten the type or widen the margin instead.
- **Don't** use stock photography or marketing-style hero imagery in chrome. Post images are screenshots, talk photos, or event photos Ivan took.
- **Don't** make the header sticky by default. The brief says non-sticky.
- **Don't** use Build Green, Amber, or Red as decoration. They are status colors only.

## 7. Tokens

`wp-content/themes/ik2/theme.json` is the single source of truth. Every value in this document is either a preset or a `settings.custom` entry in that file. `design-system/theme.json` and `design-system/colors_and_type.css` are copies kept for the prototype kits and previews.

WordPress turns both into CSS custom properties:

- **Presets** (`color.palette`, `typography.fontSizes`, `typography.fontFamilies`, `spacing.spacingSizes`, `border.radiusSizes`, `shadow.presets`) become `--wp--preset--<type>--<slug>`: `--wp--preset--color--signal`, `--wp--preset--font-size--sm-plus`, `--wp--preset--spacing--5`, `--wp--preset--border-radius--md`, `--wp--preset--shadow--sm`.
- **Custom values** (`settings.custom`) become `--wp--custom--<group>--<key>`, with camelCase keys converted to kebab-case. `color.categoryTint.webApis` is `--wp--custom--color--category-tint--web-apis`.
- **Layout widths** come from `settings.layout`: `--wp--style--global--content-size` (720px) and `--wp--style--global--wide-size` (1080px).

The `settings.custom` groups:

| Group                | CSS variable prefix                     | Values                                                                                     |
| :------------------- | :-------------------------------------- | :----------------------------------------------------------------------------------------- |
| `fontWeight`         | `--wp--custom--font-weight--`           | regular 400, medium 500, semibold 600, bold 700, heavy 800                                 |
| `lineHeight`         | `--wp--custom--line-height--`           | compact 0.9, flat 1, tight 1.1, snug 1.25, normal 1.4, comfortable 1.55, relaxed 1.7       |
| `letterSpacing`      | `--wp--custom--letter-spacing--`        | tightest -0.04em, tighter -0.03em, tight -0.02em, snug -0.01em, normal 0, wide 0.02em, wider 0.05em, widest 0.08em |
| `transition`         | `--wp--custom--transition--`            | base `200ms ease`, drawer `280ms cubic-bezier(0.22, 1, 0.36, 1)`                           |
| `focus`              | `--wp--custom--focus--`                 | width 2px, offset 3px                                                                      |
| `color.scrim`        | `--wp--custom--color--scrim`            | Ink at 30%, for the palette and drawer overlays                                            |
| `color.categoryTint` | `--wp--custom--color--category-tint--`  | article-card cover tints per category                                                      |
| `color.windowDot`    | `--wp--custom--color--window-dot--`     | close, minimize, zoom dots on terminal mockups                                             |
| `width`              | `--wp--custom--width--`                 | full 1280px                                                                                |
| `fontSize`           | `--wp--custom--font-size--`             | numeral, the 404 page number only                                                          |

How code references them:

- **SCSS partials** under `src/` use the `$` aliases in `src/styles/_tokens.scss`: `$color-signal`, `$size-sm-plus`, `$leading-relaxed`, `$tracking-wide`, `$radius-md`, `$shadow-sm`, `$transition-base`, `$focus`. That file only aliases the custom properties; it holds no values of its own.
- **Plain block CSS** (`blocks/*/style.css` and the plugin's `project-card`) has no build step, so it uses the properties directly: `var(--wp--preset--font-size--sm)`, `var(--wp--preset--border-radius--md)`, `var(--wp--custom--transition--base)`.
- **Prototype kits** load `design-system/colors_and_type.css`, which repeats the same values under the older `--color-*`, `--font-size-*`, `--radius-*` names.

To add a token, put it in `theme.json` first, alias it in `_tokens.scss` if SCSS needs it, then copy it into `design-system/theme.json` and `design-system/colors_and_type.css`. Never write a literal value in a stylesheet when a token exists.
