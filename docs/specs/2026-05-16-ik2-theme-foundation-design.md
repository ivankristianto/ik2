# IK2 theme — Header, Footer, Homepage foundation

Date: 2026-05-16
Status: Design accepted, ready for implementation plan

## Goal

Turn the freshly scaffolded `wp-content/themes/ik2/` into a working block
theme with a real Header, Footer, and Homepage that match the "Ink, Paper,
Signal" design system. Wire up an SCSS build via `@wordpress/scripts`. Lay
groundwork for the Interactivity API (command palette + instant search) as
a follow-up.

## Non-goals

- Custom post types for notes, projects, or talks. Articles and notes
  remain regular posts distinguished by category.
- Live ⌘K command palette behaviour. The button renders; the store is
  scaffolded; no keyboard or REST wiring this round.
- Single-post template polish, taxonomy archives, search results page.
- Multiple style variations under `styles/*.json`.

## Source of truth

- Tokens: `design-system/colors_and_type.css` and `design-system/theme.json`.
  Theme's `theme.json` mirrors them (already in place).
- Component reference: `samples/components/` and `samples/pages/Home.jsx`.
- Styling reference: `samples/assets/kit.css` (and `extensions.css`).
- Brand voice and design rules: `CLAUDE.md` and `design-system/README.md`.

## Theme layout

```
wp-content/themes/ik2/
├── functions.php                # bootstrap, requires inc/*
├── style.css                    # theme header only — real CSS is build/
├── theme.json                   # tokens + templateParts declaration
├── templates/
│   ├── index.html               # post archive (already present, light polish)
│   ├── front-page.html          # NEW — six pattern includes in order
│   └── 404.html                 # NEW — minimal, reuses header/footer
├── parts/
│   ├── header.html              # NEW (replaces stub)
│   └── footer.html              # NEW (replaces stub)
├── patterns/
│   ├── home-hero.php
│   ├── home-featured-topics.php
│   ├── home-evergreen-guides.php
│   ├── home-latest-notes.php
│   ├── home-projects-preview.php
│   └── home-speaking-preview.php
├── inc/
│   ├── Setup.php                # theme supports, nav menu fallback
│   ├── Assets.php               # enqueue build/ output + editor styles
│   └── Patterns.php             # register_block_pattern_category()
├── assets/
│   └── icons/                   # inline SVG copies from design-system/samples
├── src/
│   ├── index.js                 # entry — imports ./style.scss
│   ├── editor.js                # entry — imports ./editor.scss
│   ├── style.scss               # front-end stylesheet
│   ├── editor.scss              # editor overrides (small)
│   ├── styles/
│   │   ├── _tokens.scss         # @use bridge; mirrors theme.json tokens
│   │   ├── _reset.scss
│   │   ├── _layout.scss         # .container-full, .ik-section, .ik-grid-*
│   │   ├── _typography.scss
│   │   ├── _wordmark.scss
│   │   ├── _header.scss
│   │   ├── _footer.scss
│   │   ├── _hero.scss
│   │   ├── _topics.scss
│   │   ├── _guides.scss
│   │   ├── _notes.scss          # NoteRow + /now sidebar
│   │   ├── _projects.scss
│   │   └── _speaking.scss
│   └── interactivity/
│       └── cmd-palette/
│           ├── block.json       # not registered yet
│           └── view.js          # store stub, no behaviour wired
└── build/                       # gitignored, produced by wp-scripts
```

## Build pipeline

Use `@wordpress/scripts` (already a devDependency at the repo root). It
ships with Sass support via `sass-loader`, so SCSS works without extra
config beyond pointing webpack at the theme's `src/` directory.

- Add `sass` to `devDependencies` (peer of `sass-loader`).
- Create `webpack.config.js` at the repo root that extends the wp-scripts
  default and declares two entries: `index` → `src/index.js`,
  `editor` → `src/editor.js`, with `path` set to
  `wp-content/themes/ik2/build/`.
- Update `package.json` scripts:
  - `start` and `build` already exist; they will pick up the new
    `webpack.config.js` automatically.
  - No script rename needed.
- `Assets.php` enqueues:
  - `build/index.css` on `wp_enqueue_scripts` (replaces the current
    `get_stylesheet_uri()` call; `style.css` keeps only the theme
    header).
  - `build/editor.css` via `add_editor_style()` in `Setup.php`.
- `build/` is added to `.gitignore` under the theme path (verify root
  `.gitignore` covers it; if not, add an entry).

The Docker `pnpm` profile already runs `wp-scripts start`; existing
workflow stays the same.

## theme.json changes

Minimal — most tokens are in place:

- Add a `templateParts` array declaring `header` (area `header`) and
  `footer` (area `footer`).
- Add `settings.custom.width.full` = `"1280px"` (used by `.container-full`
  in CSS via `var(--wp--custom--width--full)`).
- No new colour, font, or spacing tokens — the existing palette and scale
  cover every prototype component.

`design-system/theme.json` is the canonical source. After edits, the
theme's copy must remain in sync (per `CLAUDE.md`); when this spec ships,
both files match.

## Header (`parts/header.html`)

Core blocks only. Structure:

```
Group (tag: header, full width, top padding 5, bottom padding 5)
└── Group (constrained, max-width via .container-full class, flex row, space-between)
    ├── Site Title (level 0, additional class "ik-wordmark")
    │     # styled via CSS to render "~ $ ivan ▍" using ::before / ::after
    ├── Navigation (ref: ik2-primary, additional class "ik-header__nav")
    │     # fallback nav menu auto-created on first load: Home, Articles,
    │     # Projects, Speaking, About, Contact
    └── Group (flex row, gap 3)
        ├── Buttons → Button "Search ⌘K" (additional class "ik-header__cmd",
        │   currently inert — opens nothing this round)
        └── Buttons → Button "Resume" (link to /resume, class
            "ik-header__resume")
```

The fallback nav menu is created in `Setup.php` via
`wp_create_nav_menu()` on `after_setup_theme`, idempotently. If the user
later edits the nav in the Site Editor, that becomes the source of truth.

## Footer (`parts/footer.html`)

Group with 4 Columns + bottom bar:

```
Group (tag: footer, background "soft-paper", top padding 9, bottom padding 6)
└── Group (constrained, .container-full)
    ├── Columns (4 cols, gap 6)
    │   ├── Column 1 — Brand
    │   │   ├── HTML block: wordmark markup
    │   │   ├── Paragraph: tagline ("Exploring WordPress, AI, performance,
    │   │   │   and developer tooling.")
    │   │   ├── Paragraph (small, graphite): "// @ivankristianto on the web"
    │   │   └── HTML block: row of inline-SVG social icons
    │   ├── Column 2 — Site links (heading "Site" + list)
    │   ├── Column 3 — Topics links (heading "Topics" + list)
    │   └── Column 4 — Subscribe (heading "Subscribe" + list: RSS, JSON,
    │       Email)
    └── Group (.ik-footer__bottom, flex row, justify space-between, top
        margin 8, top border 1px line)
        ├── Paragraph: "© 2026 Ivan Kristianto · Built on WordPress with a
        │   custom block theme · Ink, Paper, Signal design system."
        └── Paragraph: "Last published <date of latest post>"
```

The "last published" date stays static text in the markup for this round;
a dynamic version using `wp_get_recent_posts()` is a follow-up.

Icons (`github.svg`, `linkedin.svg`, `twitter.svg`, `wordpress.svg`,
`rss.svg`) are copied from `samples/assets/icons/` into
`wp-content/themes/ik2/assets/icons/` so the theme is self-contained.

## Homepage (`templates/front-page.html`)

The template is a thin wrapper:

```
template-part header
main (constrained, width "full")
  block pattern: ik2/home-hero
  block pattern: ik2/home-featured-topics
  block pattern: ik2/home-evergreen-guides
  block pattern: ik2/home-latest-notes
  block pattern: ik2/home-projects-preview
  block pattern: ik2/home-speaking-preview
template-part footer
```

### Patterns

Patterns live as PHP files in `patterns/` and are auto-registered by
WordPress when their header docblock declares `Title`, `Slug` (e.g.
`ik2/home-hero`), and `Categories: ik2-home`. The `ik2-home` pattern
*category* is registered once in `inc/Patterns.php` via
`register_block_pattern_category()`. No `register_block_pattern()` calls
in PHP — auto-discovery handles them.

#### `ik2/home-hero`

Static. Group with:
- Eyebrow paragraph (uppercase, monospace, graphite): `// CURRENTLY
  EXPLORING`
- Heading (level 1, hero size): "Building things on the web — mostly
  with WordPress and AI."
- Paragraph (lg, graphite): two-sentence intro.
- Buttons (2): primary "Browse articles" → /articles, secondary "About"
  → /about.

#### `ik2/home-featured-topics`

Static. Section header (eyebrow + h2 + "All articles →" link) + Columns
(3 cols × 2 rows on desktop, 1 col mobile) of six topic cards. Each card
is a Group with a small heading, a count, and a one-line blurb. Topics
mirror `samples/mockData.js`: WordPress, AI, Performance, Web APIs,
Tooling, Process.

#### `ik2/home-evergreen-guides`

Section header. Query Loop:
- `query.postType`: `post`
- `query.taxQuery`: category slug `guide`
- `query.perPage`: 4
- Layout: 2 columns
- Post Template: Group with Post Title (level 3), Post Excerpt, and a
  small meta row (read time placeholder for now).

If no posts are tagged `guide`, the block falls back to "No guides yet"
text via the Query No Results inner block.

#### `ik2/home-latest-notes`

Two-column Group (12-column → 8/4 split):
- Left: section header + Query Loop:
  - `query.taxQuery`: category slug `note`
  - `query.perPage`: 6
  - Post Template: Group with date (mono, graphite), title (h3, link),
    short excerpt.
  - Below: "Read every note →" link to `/articles`.
- Right: static `/now` widget — Group with `.ik-now` class,
  - Header row: pulsing-dot Span (CSS animation), `// /now` label,
    last-updated date.
  - Four groups: Currently building / Currently reading / Currently
    learning / Listening. Content matches `samples/pages/Home.jsx`
    verbatim for this round.
  - Footer: small attribution to nownownow.com.

#### `ik2/home-projects-preview`

Section header + 3-column Columns of project cards. Each card: project
name (h3, link placeholder), status pill (data attribute), one-line
blurb, tech-tag row. Static content from `samples/mockData.js`.

#### `ik2/home-speaking-preview`

Section header + a constrained list (max-width 720px). Each row: date
(mono, small), title + venue, kind tag. Static content from
`samples/mockData.js`.

## SCSS strategy

- `src/styles/_tokens.scss` defines SCSS variables that point at the CSS
  custom properties WordPress emits from `theme.json` (e.g.
  `$color-paper: var(--wp--preset--color--paper);`). This lets SCSS keep
  its ergonomic names while leaving runtime tokens in CSS variables.
- Per-component partials translate `samples/assets/kit.css` rules to
  scoped classes. Class names match the prototype exactly
  (`.ik-header`, `.ik-section`, `.ik-now`, …) so a developer can diff
  prototype vs. theme with `grep`.
- No CSS-in-JS, no Tailwind. Stylelint config at root already applies.

## Interactivity API scaffold

- Add `@wordpress/interactivity` to root `package.json` devDependencies.
- Create `src/interactivity/cmd-palette/block.json` with
  `apiVersion: 3`, `name: ik2/cmd-palette`, `category: theme`,
  `supports.interactivity: true`. Do **not** register the block in PHP
  this round.
- Create `src/interactivity/cmd-palette/view.js` that calls `store()`
  with an empty `state` and `actions.toggle` no-op. Lints clean. Acts as
  a known-good starting point for the follow-up that wires real
  behaviour.

## PHP architecture

`functions.php` becomes a thin loader:

```php
require_once __DIR__ . '/inc/Setup.php';
require_once __DIR__ . '/inc/Assets.php';
require_once __DIR__ . '/inc/Patterns.php';
```

Each `inc/*.php` file:
- Declares `namespace IK2\Theme;`
- Wraps actions/filters in anonymous static functions
- Stays under ~80 lines

`Patterns.php` registers the `ik2/home` pattern category. Patterns
themselves are auto-registered from the `patterns/` directory because of
their PHP header docblocks.

## Quality gates

- `composer quality` (PHPCS + PHPStan) clean for new PHP.
- `pnpm lint` (ESLint + Stylelint) clean for new JS + SCSS.
- `pnpm build` produces `wp-content/themes/ik2/build/index.css` and
  `build/index.js` (the JS is mostly empty for now, that's expected).
- Manual: open `http://ivankristianto.test` (or whatever the local stack
  exposes), confirm the homepage renders, header sticky-row works,
  footer columns line up at 1280, 1024, and 375 widths.
- Manual: open Site Editor and confirm `header`, `footer`, and the six
  patterns appear under "Ink, Paper, Signal".

## Risks and trade-offs

- **Wordmark via Site Title + CSS pseudos** — the `~ $` and blinking
  cursor are presentational, so attaching them via `::before/::after` on
  `.ik-wordmark` is correct and accessible (real text is the site
  title). Trade-off: editing the wordmark text in the Site Editor will
  change the site title globally. Acceptable.
- **Fallback nav menu** — we auto-create a nav menu on first load. If
  the user later deletes all menus, we don't re-create them (we check
  by name once). Trade-off: edge case is rare and easy to fix manually.
- **Static patterns vs. CPTs** — Projects/Talks/Topics are static this
  round. When CPTs land, patterns become Query Loops; markup for cards
  stays the same. Low rework cost.
- **wp-scripts default build expects `src/index.js`** — by adding
  `webpack.config.js` we control entry names. Verify the
  `start`/`build`/`format` scripts still behave as expected after the
  override.

## Out of scope (follow-up specs)

- ⌘K command palette wiring (Interactivity API + REST search).
- Instant search on the articles archive.
- CPTs: `note`, `project`, `talk` with seed data via wp-cli.
- Single-post template, taxonomy archives, search results.
- Style variations (`styles/dark.json` etc.).
- Last-published date in footer becomes dynamic.

## Acceptance

Done when:

1. `pnpm build` succeeds and emits the expected files.
2. `composer quality` and `pnpm lint` pass.
3. Visiting the site shows a styled homepage matching the prototype's
   visual structure (Hero → Topics → Guides → Notes+/now → Projects →
   Speaking), with the Header and Footer rendering everywhere.
4. Site Editor shows the parts and patterns under the IK2 category.
5. No new colours, fonts, shadows, or radii outside the existing token
   set.
