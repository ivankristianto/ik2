# Lab section

## Goal

Give the site a `/lab` section for small tools and experiments. A lab entry
holds a working tool (or the results of an experiment), a short write-up, and
links to any posts written about it.

The tools are built, released and hosted apart from WordPress. This repo owns
the shell only: the pages, the index, the SEO fields and the link between
entries and posts. A tool can be written in any language and never needs an
ik2 release to change.

The backlog that prompted this has a font split inspector (browser only), a
link and oEmbed preview (needs a server-side fetch), and a JPEG XL round-trip
experiment (needs native binaries and its own response headers). Each needs a
different runtime, so the runtime lives outside WordPress.

## Decisions

All ten are confirmed by Ivan, including the iframe mount (2026-11-11).

| # | Decision |
| --- | --- |
| Q1 | Shell is a `lab` post type in the ik2 plugin, modelled on `project` (ADR 0004). Entries live at `/lab/<slug>/`, are public, indexed, in the Yoast sitemap, and carry Yoast fields and a social card like any page. |
| Q2 | `/lab/` is a page with a `page-lab.html` template and a theme block that lists the entries, the same way `/projects/` works (`has_archive` stays false). Rows, not cards, matching the projects ledger. |
| Q3 | Posts link to lab entries through a hidden `ik2_lab` taxonomy on `post`, with one term per published entry. Same rules as `ik2_project`: not public, no rewrite, terms can't be made by hand, term follows the entry's slug and title. The entry page lists its posts under "Related notes", and each post links back. |
| Q4 | The link code is shared, not copied. `project-links.php` becomes a helper that takes a post type and a taxonomy, and `project` and `lab` both use it. If that refactor turns out to be awkward, the plan can fall back to a second copy, but it has to say why. |
| Q5 | Tools run on a separate instance from a separate repo with its own releases. Ivan's call: the lab is maintained apart from the app. Nothing under `services/` here, no new image in `build.yml`, no nginx route to it. Hostname: `lab.ivankristianto.com`. |
| Q6 | An entry shows its tool in an iframe pointed at the lab host. The iframe is cross-origin on purpose: tools render untrusted input (pasted CSS, metadata and images from other sites), and that must not share an origin with the wp-admin session. Routing `/lab/<slug>/app/` on the main hostname was turned down for that reason. |
| Q7 | The tool URL is post meta on the entry, not a block attribute and not free HTML. PHP builds the iframe and refuses any URL whose origin isn't the configured lab origin. A constant sets the origin (`https://lab.ivankristianto.com`), and local dev overrides it. |
| Q8 | An entry without a tool URL is valid. It renders as a write-up only. That covers experiments whose output is a results table (the JPEG XL benchmark) and anything hosted elsewhere, which gets a plain link. |
| Q9 | Server-side work for a tool is that tool's business: a Cloudflare Worker on a route of the lab hostname (chosen for the embed preview fetch) or a process in the lab instance. WordPress gets no REST routes for tools. |
| Q10 | Adding a tool to the site is content work: publish a lab entry in wp-admin and set its URL. An ik2 release is needed only when the shell itself changes. |

## Shape

**Plugin (`wp-content/plugins/ik2/`)**

- `inc/post-types/lab.php`: registers `lab` (rewrite slug `lab`, `with_front`
  false, excluded from site search like `project`, REST on).
- Meta, registered with `show_in_rest` and sanitize callbacks:
  - `lab_tool_url`: the tool's URL on the lab origin. Empty means no iframe.
  - `lab_source_url`: link to the tool's source. Optional.
  - `lab_min_height`: the iframe's height before the tool reports its own, in
    px. Optional, with a default. Set it to the tool's height on first paint
    so the write-up below doesn't move.
- `src/editor.js`: a "Lab tool" panel in the document sidebar for those three
  fields, built the same way as the existing project panel
  (`PluginDocumentSettingPanel` plus `useEntityProp`). This is how the URL is
  entered; nobody should need the Custom Fields panel.
- `blocks/lab-tool/`: a dynamic block with no attributes. `render.php` reads
  the entry's meta and prints the iframe, or nothing when the URL is empty or
  off-origin. `style.css` beside it, loaded only where the block is. A small
  view script handles the resize message.
- The shared link helper from Q4, used for `ik2_lab`.

**Theme (`wp-content/themes/ik2/`)**

- `templates/single-lab.html`: title, excerpt, the tool at `wideSize`, the
  write-up in the article column, then "Related notes".
- `templates/page-lab.html` plus `blocks/lab-archive/`: the index.
- `lab` joins `ARTICLE_POST_TYPES` in `inc/assets.php` if the write-up should
  get the article section CSS, heading anchors and lightbox. The plan decides
  after trying it, since the tool frame is wider than the article column.
- The single post template gains the backlink to lab entries, next to the
  existing project backlink.

No new tokens are expected. If the frame needs one (a border, a min height),
it goes into `theme.json` first.

## The iframe

```html
<iframe
  src="https://lab.ivankristianto.com/font-split/"
  title="Font split inspector"
  loading="lazy"
  sandbox="allow-scripts allow-forms allow-same-origin allow-downloads allow-popups allow-popups-to-escape-sandbox"
  allow="clipboard-write"
  referrerpolicy="strict-origin-when-cross-origin"></iframe>
```

- `title` is the entry title, so screen readers announce what the frame is.
- `allow-same-origin` keeps the tool on its own origin (it needs that to call
  its own API and use storage). It grants nothing on `www`, because the two
  origins differ.
- `allow-popups` lets a tool open its source or an external link in a new tab
  with `target="_blank"`, and `allow-popups-to-escape-sandbox` keeps that tab
  from inheriting the sandbox. Top navigation stays off, so a tool can't
  replace the page it sits in. A plain link inside the frame would load in the
  frame, which is why tool links must open a new tab.
- Height: the tool posts `{ type: 'ik2-lab:resize', height: <px> }` to its
  parent. The view script accepts it only when `event.origin` is the lab
  origin and `event.source` is that iframe's window, then clamps the value.
  Without a message the frame keeps `lab_min_height`.
- Check the state of CSS `frame-sizing` (responsive iframes) when building
  this. If a shipped browser supports it, use it and keep the message as the
  fallback.
- The site's CSP has no `frame-src` today, so the iframe needs no policy
  change. If `frame-src` or `default-src` is ever added, the lab origin has to
  be in it.

## Contract for a tool

This repo doesn't build tools, but the shell depends on each one doing the
following. The lab repo's own spec carries the detail.

- Served at `https://lab.ivankristianto.com/<slug>/`, with any API under the
  same hostname.
- Sends `Content-Security-Policy: frame-ancestors https://www.ivankristianto.com`
  and no `X-Frame-Options: SAMEORIGIN`, or the browser refuses to frame it.
- Sends no `Cross-Origin-Opener-Policy` or `Cross-Origin-Embedder-Policy`
  that would stop it loading inside the frame.
- Sends `X-Robots-Tag: noindex, indexifembedded`, so search results point at
  the WordPress entry and not the bare tool. Confirm current Google support
  for `indexifembedded` before relying on it.
- Posts the resize message on load and whenever its height changes.
- Works when opened directly. It shows a link back to its entry only then
  (`window.top === window`), since inside the frame the reader is already on
  the entry. Every other link opens a new tab.
- Follows the site's design rules: light only, system fonts for its own
  chrome, the one accent, no shadows beyond the two presets. It gets the
  values from a copy of `design-system/tokens.css`, synced from a tagged ik2
  release.
- Needs no account and keeps what a visitor pastes in the browser unless the
  tool's purpose is a server call.

## Changed during the build

- **Origin setting.** `IK2_LAB_ORIGIN` is read from a constant or an env var,
  because the local `wp-config.php` is baked on first boot and a later define
  never lands. The check is "same scheme, host and port as the configured
  origin", so a local `http://localhost:8787` works while prod stays https.
- **A link under the frame.** "Open the tool in a new tab", for small screens
  and for anyone who prefers the tool on its own.
- **No `frame-sizing`.** I found no guidance or shipped support to lean on, so
  the resize message is the only mechanism.
- **Origin check in JS.** The listener uses the `Origin` API
  (`Origin.from( event ).isSameOrigin()`) where the browser has it and
  compares parsed `URL` origins elsewhere.
- **Reporting height.** A tool should report its content height (for example
  `document.body` through a `ResizeObserver`). `documentElement.scrollHeight`
  never drops below the frame's own height, so the frame could grow but not
  shrink.
- **No new CSS for the pages.** The entry header, related notes, post backlink
  and index reuse the project classes. The index block lists the
  `ik2-project-card-style` and `ik2-projects-archive-style` handles instead
  of shipping a stylesheet.
- **The editor bundle.** `src/editor.js` was never a webpack entry, so the
  project details panel had never loaded. Adding the entry for the lab panel
  turned the project panel on too.

## Acceptance

- `/lab/` lists published entries. `/lab/<slug>/` renders title, tool, write-up
  and related notes, and appears in the Yoast sitemap with a BreadcrumbList of
  Home > Lab > name.
- Ticking an entry in a post's sidebar makes the post appear under the entry's
  "Related notes" and adds the backlink on the post. Unpublishing the entry
  removes both and keeps the term.
- An entry with `lab_tool_url` on the lab origin shows the iframe. A URL on
  any other origin shows no iframe, with a PHPUnit test on the origin check.
- A resize message from the lab origin changes the frame height. The same
  message from another origin or another window is ignored.
- An entry with no tool URL renders as a write-up with no empty frame.
- The frame reserves `lab_min_height` before the tool loads, and an entry
  whose value matches its tool adds no layout shift (CLS stays at the page's
  current score).
- Before the post type is registered, confirm nothing owns `/lab` already: no
  page, post, category or legacy redirect with that slug, locally and on prod.
- Keyboard focus moves into and out of the frame in order, and the frame has
  an accessible name.
- `composer quality` and `pnpm lint` pass. Lighthouse on an entry page shows no
  new CSP or accessibility findings.

## Out of scope

- The lab repo, its container, its Dokploy app, DNS for the subdomain and the
  Worker. They get their own spec in that repo.
- Any single tool, including the three in the backlog.
- Lab entries in the command palette, the WebMCP tools and `llms.txt`. Worth a
  follow-up once there are entries to list.
- Comments on lab entries.

## Open questions

1. Is the lab repo public or private? This repo is public. A public lab repo
   lets `lab_source_url` point readers at each tool's code.
2. Does "Lab" go in the header nav from the first release, or only once there
   are two or three entries?
3. Name of the lab repo.
