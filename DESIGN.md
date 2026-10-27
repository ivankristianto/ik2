---
name: "Ivan Kristianto: Ink, Paper, and Signal"
description: Calm, technical, reading-first design system for a personal engineering blog. Borders and whitespace do the hierarchy work.
colors:
  paper: "#FCFCFA"
  surface: "#FFFFFF"
  soft-paper: "#EFEFEB"
  ink: "#1C1D1B"
  graphite: "#494A46"
  dust: "#5C5D58"
  line: "#DCDCD5"
  rule: "#B6B6AE"
  signal: "#2F5D50"
  signal-deep: "#234539"
  signal-soft: "#E1EAE5"
  code-paper: "#F1F1ED"
  build-green: "#3D6B1F"
  amber: "#8A5A14"
  red: "#A33A2A"
typography:
  hero:
    fontFamily: "ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif"
    fontSize: "clamp(3rem, 2.25rem + 3.3333vw, 4.75rem)" # preset xxx-large
    fontWeight: 700 # custom.fontWeight.bold
    lineHeight: 1.1 # custom.lineHeight.tight
    letterSpacing: "-0.04em" # custom.letterSpacing.tightest
  display:
    fontFamily: "ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif"
    fontSize: "clamp(2.5rem, 1.9643rem + 2.381vw, 3.75rem)" # preset xx-large
    fontWeight: 700
    lineHeight: 1.1
    letterSpacing: "-0.04em"
  headline:
    fontFamily: "ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif"
    fontSize: "clamp(2rem, 1.6786rem + 1.4286vw, 2.75rem)" # preset x-large
    fontWeight: 700
    lineHeight: 1.1
    letterSpacing: "-0.04em"
  title:
    fontFamily: "ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif"
    fontSize: "clamp(1.5rem, 1.2857rem + 0.9524vw, 2rem)" # preset large
    fontWeight: 700
    lineHeight: 1.1
    letterSpacing: "-0.03em"
  subtitle:
    fontFamily: "ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif"
    fontSize: "clamp(1.25rem, 1.1429rem + 0.4762vw, 1.5rem)" # preset medium
    fontWeight: 700
    lineHeight: 1.25 # custom.lineHeight.snug
    letterSpacing: "-0.02em"
  body:
    fontFamily: "ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif"
    fontSize: "clamp(1.125rem, 1.0714rem + 0.2381vw, 1.25rem)" # preset base
    fontWeight: 400 # custom.fontWeight.regular
    lineHeight: 1.7 # custom.lineHeight.relaxed
    letterSpacing: "0"
  ui:
    fontFamily: "ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif"
    fontSize: "clamp(1rem, 0.9732rem + 0.119vw, 1.0625rem)" # preset small
    fontWeight: 400
    lineHeight: 1.55 # custom.lineHeight.comfortable
  label:
    fontFamily: "ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, Liberation Mono, monospace"
    fontSize: "clamp(0.875rem, 0.8482rem + 0.119vw, 0.9375rem)" # preset x-small
    fontWeight: 400
    lineHeight: 1.4 # custom.lineHeight.normal
  eyebrow:
    fontFamily: "ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, Liberation Mono, monospace"
    fontSize: "clamp(0.875rem, 0.8482rem + 0.119vw, 0.9375rem)" # preset x-small
    fontWeight: 400
    letterSpacing: "0"
rounded:
  sm: "4px"
  md: "6px"
  lg: "8px"
  pill: "999px"
  round: "50%"
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
    padding: "8px 16px"
    height: "44px"
  button-primary-hover:
    backgroundColor: "{colors.signal-deep}"
    textColor: "{colors.paper}"
  button-primary-current:
    backgroundColor: "{colors.signal}"
    textColor: "{colors.paper}"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.graphite}"
    rounded: "{rounded.md}"
    padding: "12px"
    height: "44px"
  button-secondary-hover:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
  card:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "24px 32px"
  article-card-cover:
    backgroundColor: "{colors.soft-paper}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "16px"
    typography: "{typography.label}"
  tag:
    backgroundColor: "{colors.soft-paper}"
    textColor: "{colors.graphite}"
    rounded: "{rounded.pill}"
    padding: "2px 8px"
    typography: "{typography.micro}"
  tag-hover:
    backgroundColor: "{colors.signal-soft}"
    textColor: "{colors.signal-deep}"
  filter-pill:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.graphite}"
    rounded: "{rounded.pill}"
    padding: "8px 12px"
    typography: "{typography.label}"
  filter-pill-active:
    backgroundColor: "{colors.signal-soft}"
    textColor: "{colors.signal-deep}"
  nav-link:
    backgroundColor: "transparent"
    textColor: "{colors.graphite}"
    padding: "8px 12px"
    height: "44px"
  nav-link-current:
    backgroundColor: "transparent"
    textColor: "{colors.ink}"
  palette-input:
    backgroundColor: "transparent"
    textColor: "{colors.ink}"
    padding: "16px 24px"
  palette-row-active:
    backgroundColor: "{colors.signal-soft}"
    textColor: "{colors.signal-deep}"
    padding: "12px 24px"
  code-block:
    backgroundColor: "{colors.code-paper}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "24px"
---

# Design System: Ink, Paper, and Signal

## Overview

**Creative North Star: "The Engineering Notebook on Drafting White"**

This is the design of a well-kept lab notebook. Open it and the page is drafting white, the ink is near-black, and the only color you see is the occasional mark in bookcloth green, the color of a cloth-bound notebook spine: a link the writer wants you to follow, a tag for a topic, a focus ring that won't let you lose your place. Everything else is type, line, and whitespace doing the hierarchy work. The personality comes from the contrast between the system sans body and the system mono metadata. The page is for reading, the metadata is for scanning, and the two never get confused.

The system rejects the marketing register. It does not look like a Medium template, a Substack newsletter, a SaaS landing page, or an AI tool launch. It does not animate on scroll, it does not bounce on hover, it does not have a hero image. It is the design of someone who already has a job and is publishing because they have something to say. When in doubt, widen the margins instead of adding a shadow; tighten the type instead of adding an icon.

**Key Characteristics:**

- Drafting white page background, never cream. Pure white Surface is for cards only.
- One accent, Bookcloth green, used on 10% of a screen or less.
- System fonts only; no webfont download.
- Borders, whitespace, and type contrast carry hierarchy. No gradients, no glassmorphism, no decorative shadows.
- Mono is the signal: dates, tags, metadata, eyebrows, code. Eyebrows are lowercase with no `//` prefix.
- Light only. A future dark mode will arrive through `theme.json` (a style variation or settings), not hand-written CSS.

## Colors

A restrained palette of near-neutral greys plus one bookcloth green accent. No neutral leans warm or cool enough to read as a color, including the two text greys. Status colors exist, but only for status.

### Primary

- **Bookcloth** (`signal`): the single accent. Links, focus rings, nav hover, the current-page underline, the hero CTA underline, and the Resume button when you are on `/resume`. Its rarity is what makes it read as signal. The slug stays `signal` from the retired blue and terracotta eras; only the value moved.
- **Deep Bookcloth** (`signal-deep`): hover state for anything filled with ink or the accent, and the text color on every active state that sits on Bookcloth Wash.
- **Bookcloth Wash** (`signal-soft`): the background for active states. Selected filter pills, tag hover, the palette's active row, the current item in the mobile nav drawer. Never a decorative fill.

### Neutral

- **Paper** (`paper`): the page background and the command palette footer.
- **Surface** (`surface`): project cards, the palette panel, filter pills at rest, the header search trigger. Reads as a raised sheet against Paper.
- **Soft Paper** (`soft-paper`): muted sections, tag backgrounds, covers for misc and uncategorized articles, drawer row hover.
- **Ink** (`ink`): body and heading text, the primary button fill. Near-black so the page never feels harsh.
- **Graphite** (`graphite`): secondary copy, excerpts, nav links at rest, tag text, eyebrows.
- **Dust** (`dust`): dates, reading time, separators, keyboard hints. Clears WCAG AA (6.47:1) on Paper and 5.76:1 on Soft Paper.
- **Line** (`line`): every resting border: cards, pills, tags, code, dividers, the palette rules.
- **Rule** (`rule`): the hover border on cards and pills, and the blockquote rule.
- **Code Paper** (`code-paper`): inline code, `<pre>` blocks, and the 404 terminal. Code text is plain Ink.

### Status

- **Build Green** (`build-green`): success, "Active" project status, the "updated" callout. A moss green, kept well apart from the accent's blue-green so a status never reads as a link.
- **Amber** (`amber`): warnings, "Experiment" project status, the "outdated" callout.
- **Red** (`red`): errors only.

### Supporting custom colors

These live in `settings.custom.color`, outside the palette, so they never appear in the editor's color picker.

- **Syntax** (`syntax.*`): code block highlighting. Comments in Graphite, keywords and tags in Deep Bookcloth, strings and numbers in walnut `#7A4E1C` (5.8:1 on Code Paper); everything else stays Ink.
- **Category washes** (`categoryWash.*`): article-card covers, one per category (WordPress, AI, performance, web APIs, tooling, guide, note, talk). Written in OKLCH at 92% lightness and 0.03 chroma with only the hue changing, so every cover carries the same weight and the Graphite label keeps 6.97 to 7.13:1 on all of them. Misc and uncategorized posts stay on Soft Paper. Palette presets stay hex because the block editor cannot parse `oklch()`; these never reach its color picker.
- **Scrim** (`scrim`): Ink at 30%, behind the command palette and the mobile nav drawer.

### Named Rules

**The One Voice Rule.** Bookcloth covers 10% of a screen at most. If it starts to read as a color theme instead of a signal, replace the decorative uses with weight, scale, or whitespace.

**The No Pure White Page Rule.** The page is Paper, never `#FFFFFF`. White is reserved for cards and panels, where it reads as a raised sheet. Paper is also never cream.

**The Status-Only Rule.** Build Green, Amber, and Red mark status and nothing else. Categories get a pale `categoryWash`, never a status color, and the label still names the category.

## Typography

**Display Font:** System Sans, `ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif`
**Body Font:** the same System Sans stack
**Label/Mono Font:** System Mono, `ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace`

**Character:** the page speaks in the operating system's own voice. The mono stack carries the engineering signal. The contrast between sans prose and mono metadata is most of the visual personality. No webfonts ship.

### Hierarchy

Sizes are `theme.json` font-size presets. Weights, line heights, and tracking come from `settings.custom`. All headings share bold weight, tight leading, and tightest tracking from `styles.elements.heading`; the roles below loosen that where the size drops.

- **Hero** (bold, `xxx-large`, tight leading, tightest tracking): the home hero title and the resume name. Phones keep a 10ch measure so the title still fills the screen.
- **Display** (bold, `xx-large`, tight, tightest): the 404 title.
- **Headline** (bold, `x-large`, tight, tightest): page titles and article H2s. Archive headers, the single-article H1, section titles on phones.
- **Title** (bold, `large`, tight, tighter): section titles, article H3 from 900px up, the hero CTA.
- **Subtitle** (bold, `medium`, snug, tight): article H3 on phones, lead paragraphs, project names.
- **Body** (regular, `base`, relaxed): the page default, article prose and card titles. It runs from 18px on phones to 20px on desktop, capped at the 960px content width.
- **UI** (regular, `small`): nav-adjacent copy, excerpts, the hero sub copy, buttons, code blocks.
- **Label** (mono, `x-small`, normal leading): article-card meta, filter pills, cover labels, tags, status badges, keyboard hints, captions, the footer.
- **Eyebrow** (mono, `x-small`, lowercase, normal tracking, Graphite): the line above section titles. No `//` prefix.

**The 14px Floor Rule.** Nothing a reader is meant to read is set below `x-small` (14px, 15px on wide screens). The only smaller text is inline code inside `x-small` copy, at 0.9em.

**The Scales-With-The-Reader Rule.** Every size is in rem, and every fluid size is `clamp(min, rem + vw, max)` with a rem term in the middle, so it follows the reader's browser text size and zoom. Never write a pure-vw middle value. Breakpoints are in em for the same reason.

### Named Rules

**The Mono-as-Signal Rule.** Mono is reserved for things that are not prose: dates, tags, reading time, file paths, code, palette chrome, eyebrows. A paragraph set in mono has lost its meaning.

**The Tight Headline Rule.** Headings are bold with tight leading and negative tracking. They read as confident, never as marketing.

**The em Exception.** Inline code, `kbd`, and `sup` size themselves in `em` so they track the paragraph around them. That is the one place a font size is not a preset.

## Layout

The page is a single centered column driven by WordPress layout. Content width is 60rem (`settings.layout.contentSize`, 960px at the default text size), wide alignment is 75rem (`wideSize`, 1200px), and full-bleed chrome caps at 80rem (`custom.width.full`, 1280px). The widths are in rem so the measure holds when a reader enlarges their text. Single articles use the content width as is. Page templates set `custom.width.full` on their post-content, so they don't use it. Root padding is fluid between 24px and 32px (`clamp(spacing-5, 5vw, spacing-6)`), and root-padding-aware alignments keep full-width sections flush with the viewport.

Spacing is a ten-step scale from 4px to 128px (`spacing.spacingSizes` 1 to 10). Sections breathe at 96px of block padding on desktop and 48px on phones. Section heads sit 48px above their content (24px on phones). Cards and grids use 24px to 32px gaps. Custom spacing values are disabled in the editor, so every gap, margin, and padding resolves to a step.

Responsive behavior keys off a few breakpoints:

- **640px**: phone layout. Section padding, hero spacing, and title tiers step down.
- **768px**: the header switches to the hamburger drawer (six links and the Resume CTA no longer fit inline).
- **900px**: the hero drops its two-column grid and the portrait moves under the copy.

Several partials still use one-off breakpoints (600, 700, 720, 760, 800, 960, 1000, 1024px). Treat 640, 768, and 900 as the canonical set when adding new rules.

**The 960 Rule.** Article content caps at 960px with a 20px body on desktop, about 90 characters a line. At 720px and 18px, long paragraphs stacked into a wall. Don't widen the column further without raising the body size to match, and cap short prose outside articles with `custom.width.measure` (60ch) instead of the content width.

**The Whitespace First Rule.** When a layout feels empty, widen the margins before adding a border, a shadow, or an icon.

## Elevation & Depth

The system is flat by default. Depth comes from the Paper-to-Surface step, a 1px Line border, and alternating Paper and Soft Paper sections. Shadows appear only as a response to state, or on the one true overlay.

### Shadow Vocabulary

- **Small** (`shadow.presets.sm`, `0 1px 2px rgba(0, 0, 0, 0.04), 0 1px 1px rgba(0, 0, 0, 0.03)`): card hover on project cards and the 404 page's cards. A 1px lift, paired with the border darkening from Line to Rule. The border change is the larger half of the gesture.
- **Medium** (`shadow.presets.md`, `0 6px 24px rgba(0, 0, 0, 0.08)`): the command palette panel. The only resting shadow in the system.

Core's default shadow presets are disabled, so these two are the only shadows the editor offers.

### Named Rules

**The Flat-By-Default Rule.** Surfaces are flat at rest. When something looks missing, try wider margins, stronger type contrast, or a 1px border first. If none of those work, the layout is wrong, not the elevation.

**The Border-as-Elevation Rule.** A 1px Line border says "card" more honestly than a shadow. The border is the object; the shadow is the lift on interaction.

**The 2014-App Test.** If a card looks like a 2014 iOS app (big soft shadow, large blur, low offset), cut the shadow.

## Shapes

Corners are gently rounded and small. Radius presets live in `border.radiusSizes`:

- **Small** (`sm`): inline code, keyboard hints, mobile drawer rows.
- **Medium** (`md`): the default. Buttons, cards, article-card covers, featured images, `<pre>` blocks, the header search trigger.
- **Large** (`lg`): the command palette panel only.
- **Pill** (`pill`): tags, filter pills, project status badges. Pills always mean "this is a label".
- **Round** (`round`): the grey window dots and other circular glyphs.

Borders are 1px hairlines everywhere except the 2px focus ring. Media and covers clip to their radius with `overflow: clip`.

**The Pill-Means-Label Rule.** Buttons are never pills. A pill shape tells the reader they are looking at a tag, a filter, or a status.

## Components

Every component reads tokens. No hardcoded hex, px, or ms appears in component CSS.

### Buttons

- **Shape:** medium radius, 44px minimum height so every button is a full touch target.
- **Primary:** the header Resume CTA. Ink fill, Paper text, `x-small` size, 8px by 16px padding. Hover swaps the fill to Deep Bookcloth. On `/resume` it fills with Bookcloth to confirm where you are.
- **Secondary:** the header search trigger. Surface fill, 1px Line border, Graphite text, 12px padding, with a Micro-size `kbd` hint. Hover darkens the border to Rule and the text to Ink. The fill does not change.
- **Hero CTA:** the `hero-cta` block style on core Button. An oversized bold text link at `large` size (`x-large` on phones) with a mono arrow, underlined by a 2px Bookcloth bar painted as a sized background. Hover thickens the bar to 4px and turns the text Deep Bookcloth.
- **Focus:** `2px solid` Bookcloth with a 3px offset (`custom.focus`). Always visible. Rows inside a scrolling list or track (palette results, lightbox slides) draw the same ring inset (`$focus-offset-inset`), because the scroller would clip it outside.
- **Transitions:** `custom.transition.base` (200ms ease) on color, background, border, and box-shadow only. No transform, no scale.

### Chips

- **Tags:** Soft Paper fill, 1px Line border, Graphite text, pill radius, 2px by 8px padding, Micro type. Always lowercase (`wordpress`, `security`, `cli`). Hover and focus move to Bookcloth Wash with a Bookcloth border and Deep Bookcloth text.
- **Filter pills:** the articles archive filter. Surface fill, Line border, Graphite text, Label type, 8px by 12px padding. Hover darkens border and text. The active filter uses the same Bookcloth Wash treatment as tag hover.
- **Status badges:** project cards. Paper fill, Micro type with wider tracking. "Active" and "Experiment" color the text and a 30% border with Build Green or Amber.

### Cards

- **Project card:** Surface fill, 1px Line border, medium radius, 24px by 32px padding. Hover darkens the border to Rule and adds the small shadow. Nothing moves.
- **Article card:** no frame. A 16:9 cover (the category wash, medium radius, a mono label centered in Graphite), then mono meta in Dust, the card title, a Graphite excerpt, and tags pinned to the bottom. The cover gives the card its edge; a border would double it.

### Inputs / Fields

The site has no boxed form fields. The two inputs sit flush inside a row:

- **Command palette input:** transparent, borderless, Ink text at `small`, behind a Bookcloth mono caret. The row's bottom rule turns Bookcloth on `:focus-within`, which stands in for the outline the input drops.
- **404 terminal input:** mono `small` on the terminal surface, transparent and borderless, with a Bookcloth caret.

### Navigation

- **Desktop:** plain links in Graphite at `x-small`, 8px by 12px padding, 44px tall, no underline at rest. Hover turns Bookcloth. The current page goes Ink with a Bookcloth underline (0.08em thick, 0.18em offset).
- **Mobile (768px and below):** the core navigation hamburger opens a drawer over the scrim. Rows are 56px tall at `base` size with small radius; hover fills Soft Paper, and the current item fills Bookcloth Wash with Deep Bookcloth semibold text. The drawer slides in on `custom.transition.drawer`, the one motion exception.
- **Not sticky.** The header scrolls away with the page.

### Code Blocks

- **Inline code:** Code Paper fill, Ink text, 1px Line border, small radius, mono at 0.9375em.
- **`<pre>`:** the same colors and border, medium radius, 24px padding, mono `small` with relaxed leading. Long lines wrap instead of scrolling.

### Command Palette (signature component)

- **Overlay:** the scrim with a 2px backdrop blur, the one permitted `backdrop-filter`, justified because the palette is a true modal.
- **Panel:** Surface fill, 1px Line border, large radius, medium shadow, 640px wide at most.
- **Rows:** `small` sans labels with a mono glyph in Dust. The active row fills Bookcloth Wash with Deep Bookcloth text and a Bookcloth glyph.
- **Chrome:** mono throughout for group titles (uppercase, wider tracking), the Esc hint, and the Paper footer with `kbd` keys.

## Do's and Don'ts

### Do:

- **Do** use Bookcloth for links, focus rings, current-page marks, and active states, and keep it under 10% of the screen.
- **Do** set the page on Paper and reserve Surface for cards and panels.
- **Do** use mono for dates, tags, reading time, eyebrows, file paths, and code. Never for prose.
- **Do** cap article content at 960px, wide images at 1200px, full-bleed sections at 1280px.
- **Do** give every interactive element a `:focus-visible` ring: `2px solid` Bookcloth, 3px offset.
- **Do** lift cards on hover with a Line-to-Rule border change plus the small shadow. No transform, no scale.
- **Do** transition only color, background, border, and box-shadow at `transition.base`. The mobile drawer slide is the only exception.
- **Do** keep touch targets at 44px or taller.
- **Do** write CTAs verb-first with no period: **Browse Articles**, **Read more**, **Subscribe via RSS**.
- **Do** show dates in monospace, ISO 8601: `2020-07-08`.
- **Do** add a token to `theme.json` before using a new value anywhere.

### Don't:

- **Don't** bring back the retired blue accent (`#2563EB`).
- **Don't** add a webfont, `@font-face`, or Google Fonts link.
- **Don't** use shadows beyond the small preset on card hover and the medium preset on the palette.
- **Don't** use gradients, except the hero CTA's solid Bookcloth bar painted as a sized background.
- **Don't** use glassmorphism, frosted panels, grain, or noise. The palette overlay blur is the only `backdrop-filter`.
- **Don't** put a colored side stripe (a `border-left` thicker than 1px) on cards, callouts, list items, or alerts. Use a full 1px border, a background tint, or a mono label.
- **Don't** use gradient text.
- **Don't** build the SaaS hero-metric block (big number, small label, supporting stats).
- **Don't** repeat identical icon-heading-text card grids. Article cards have covers, guides have numbers, notes lead with the date.
- **Don't** reach for a modal first. Inline disclosure, an expanded section, or a new page come before it.
- **Don't** animate layout properties, bounce, scale, or add entrance and scroll animations.
- **Don't** put emoji in chrome (nav, footer, buttons, headings, eyebrows).
- **Don't** use em dashes in copy.
- **Don't** add an icon next to a heading to make it feel designed.
- **Don't** use stock photography or marketing hero imagery. Post images are screenshots, talk photos, or event photos Ivan took.
- **Don't** make the header sticky.
- **Don't** add dark-mode CSS or `prefers-color-scheme` blocks.

## Tokens

`wp-content/themes/ik2/theme.json` is the single source of truth. Every value in this document is a preset or a `settings.custom` entry in that file. `design-system/theme.json` and `design-system/tokens.css` are generated from it by `pnpm tokens` for the prototype kits and previews.

WordPress turns both into CSS custom properties:

- **Presets** (`color.palette`, `typography.fontSizes`, `typography.fontFamilies`, `spacing.spacingSizes`, `border.radiusSizes`, `shadow.presets`) become `--wp--preset--<type>--<slug>`: `--wp--preset--color--signal`, `--wp--preset--font-size--x-small`, `--wp--preset--spacing--5`, `--wp--preset--border-radius--md`, `--wp--preset--shadow--sm`.
- **Custom values** (`settings.custom`) become `--wp--custom--<group>--<key>`, with camelCase keys converted to kebab-case. `color.syntax.string` is `--wp--custom--color--syntax--string`.
- **Layout widths** come from `settings.layout`: `--wp--style--global--content-size` (60rem) and `--wp--style--global--wide-size` (75rem).

Font sizes: eight steps, `x-small`, `small`, `base`, `medium`, `large`, `x-large`, `xx-large`, `xxx-large`, shaped after the Human Made scale. Each is `clamp(min, rem + vw, max)`, fluid between 360px and 1200px. WordPress's own fluid typography is off, so they render exactly as written. The slugs are new names on purpose: stored page content still carries old preset classes such as `has-xl-font-size`, and those must not match a different size.

The `settings.custom` groups:

| Group                | CSS variable prefix                     | Values                                                                                     |
| :------------------- | :-------------------------------------- | :----------------------------------------------------------------------------------------- |
| `fontWeight`         | `--wp--custom--font-weight--`           | regular 400, medium 500, semibold 600, bold 700, heavy 800                                 |
| `lineHeight`         | `--wp--custom--line-height--`           | compact 0.9, flat 1, tight 1.1, snug 1.25, normal 1.4, comfortable 1.55, relaxed 1.7       |
| `letterSpacing`      | `--wp--custom--letter-spacing--`        | tightest -0.04em, tighter -0.03em, tight -0.02em, snug -0.01em, normal 0, wide 0.02em, wider 0.05em, widest 0.08em |
| `transition`         | `--wp--custom--transition--`            | base `200ms ease`, drawer `280ms cubic-bezier(0.22, 1, 0.36, 1)`                           |
| `focus`              | `--wp--custom--focus--`                 | width 2px, offset 3px                                                                      |
| `color.scrim`        | `--wp--custom--color--scrim`            | Ink at 30%, for the palette and drawer overlays                                            |
| `color.syntax`       | `--wp--custom--color--syntax--`         | comment, keyword, string colors for code block highlighting                                |
| `width`              | `--wp--custom--width--`                 | full 80rem (1280px)                                                                        |
| `fontSize`           | `--wp--custom--font-size--`             | numeral, the 404 page number only                                                          |

How code references them:

- **SCSS partials** under `src/` use the `$` aliases in `src/styles/_tokens.scss`: `$color-signal`, `$size-x-small`, `$leading-relaxed`, `$tracking-wide`, `$radius-md`, `$shadow-sm`, `$transition-base`, `$focus`. That file only aliases the custom properties; it holds no values of its own.
- **Plain block CSS** (`blocks/*/style.css` and the plugin's `project-card`) has no build step, so it uses the properties directly: `var(--wp--preset--font-size--x-small)`, `var(--wp--preset--border-radius--md)`, `var(--wp--custom--transition--base)`.
- **Prototype kits** load `design-system/colors_and_type.css`, which aliases the generated `tokens.css` under the older `--color-*`, `--font-size-*`, `--radius-*` names. It holds no values of its own.

To add a token, put it in `theme.json` first, alias it in `_tokens.scss` if SCSS needs it, run `pnpm tokens`, and alias it in `design-system/colors_and_type.css` if the kits need it. Never write a literal value in a stylesheet when a token exists.
