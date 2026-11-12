# Lab section: implementation plan

Spec: `docs/specs/2026-11-11-lab-section-design.md`. The spec decides
behaviour; this plan orders the work and fixes the names. Branch:
`lab-section`. Covers the shell in this repo only. The lab repo, its instance
and the Worker are planned in that repo.

## Rules for every step

- Work in the main checkout on the branch (Docker bind-mounts it). No worktrees.
- One commit per step below, Conventional Commits, staged by file name.
- Never run a formatter or `--fix` without a file list.
- After adding a PHP file or a `require_once`, run `docker compose restart app`
  before testing (opcache).
- Named functions for hook callbacks, short doc blocks, why-comments only,
  tokens instead of literal values in CSS, no `!important`.

## Names (fixed)

| Thing | Name |
| --- | --- |
| Post type | `lab`, rewrite slug `lab`, namespace `IK2\Plugin\PostTypes\Lab` |
| Taxonomy on `post` | `ik2_lab` |
| Term meta, entry meta for the link | `lab_id`, `_ik2_lab_term` |
| Entry meta | `lab_tool_url`, `lab_source_url`, `lab_min_height` |
| Schema option | `ik2_lab_schema_version` |
| Lab origin | `IK2_LAB_ORIGIN` constant, default `https://lab.ivankristianto.com` |
| Blocks | `ik2/lab-tool` (plugin), `ik2/lab-archive` (theme) |
| Resize message | `{ type: 'ik2-lab:resize', height: <px> }` |
| Read API | `Lab\labs_for_post( int $post_id )`, `Lab\posts_for_lab( int $lab_id )`, `Lab\get_tool_data( int $lab_id )` |

`get_tool_data()` returns `array{url:string, source:string, min_height:int}`,
with `url` already emptied when it fails the origin check. Theme code calls
the read API behind `function_exists`, the way the project patterns do.

## Steps

### 1. Share the link code (`refactor(projects)`)

- New `wp-content/plugins/ik2/inc/post-types/entry-links.php`, namespace
  `IK2\Plugin\PostTypes\EntryLinks`. Move the bodies of
  `register_project_taxonomy`, `sync_term`, `delete_project_term`,
  `projects_for_post` and `posts_for_project` out of `project-links.php` into
  functions that take the post type, taxonomy and the two meta keys.
- `project-links.php` keeps every public function name and hook callback as a
  thin wrapper, so `single-project-writing.php`, `single-article-project.php`,
  the activity module and the blocks need no change.
- No behaviour change. Check: the projects checklist in a post sidebar, a
  project page's "Writing about it" list and a post's project backlink all
  render as before. `composer quality`.
- If the wrappers end up longer than the code they replace, stop and copy
  instead, and note the reason in the spec's Q4.

### 2. Post type, meta and links (`feat(lab)`)

- Before anything else, confirm `/lab` is free: `wp post list --name=lab
  --post_type=any`, `wp term list category --slug=lab`, a look through
  `legacy-redirects.php`, and `curl -sI https://www.ivankristianto.com/lab/`.
- `inc/post-types/lab.php`: `bootstrap()`, `register()` (copy the `project`
  args, `menu_icon` `dashicons-hammer`, no block template), the three meta
  fields, `get_tool_data()`.
- `inc/post-types/lab-rules.php`: pure helpers with no WordPress calls and no
  `ABSPATH` guard, so PHPUnit loads the file directly:
  `is_lab_url( string $url, string $origin ): bool` (https only, exact scheme,
  host and port match, no userinfo) and
  `clamp_height( int $px ): int`. `tests/php/LabRulesTest.php` covers a good
  URL, a lookalike host (`lab.ivankristianto.com.evil.test`), `http`,
  userinfo, a different port and an empty string.
- `inc/post-types/lab-links.php`: the `ik2_lab` taxonomy and wrappers over
  `EntryLinks`, plus `maybe_upgrade()` that flushes rewrites and clears
  Yoast's `noindex-lab` if set.
- Wire `PostTypes\Lab\bootstrap()` and `on_activate()` in `inc/namespace.php`.
- `docker-compose.yml`: define `IK2_LAB_ORIGIN` for local dev (a localhost
  port, see Verification).

### 3. Editor panel (`feat(lab)`)

- `src/editor.js`: a second `registerPlugin` with a "Lab tool" panel for the
  three fields, shown only on `lab` entries. Follow the existing project
  panel. `pnpm build`, `pnpm lint`.

### 4. The `lab-tool` block (`feat(lab)`)

- `wp-content/plugins/ik2/blocks/lab-tool/`: `block.json` (no attributes,
  `supports.align` wide), `render.php`, `style.css`.
- `render.php` prints nothing without a valid URL. Otherwise the iframe from
  the spec, inside a wrapper that carries `data-lab-origin` and reserves
  `lab_min_height` through an inline custom property, not a literal in CSS.
- View script: listens for the resize message and sets the frame height. The
  accept-or-reject logic is a pure function in its own file
  (`event.origin`, `event.source`, message shape, clamp) with
  `tests/lab-tool-rules.test.mjs`.
- Decide how the script ships by reading how `code-highlight` does it in
  `wp-content/plugins/ik2/webpack.config.js` and `inc/code-highlight.php`. It
  must load only on pages with the block, carry a version in its URL (nginx
  caches `.js` for 30 days), and pass `pnpm test:csp`.
- Check `frame-sizing` support as the spec asks. Use it if it has shipped.

### 5. Entry page (`feat(lab)`)

- `templates/single-lab.html`, from `single-project.html`: breadcrumbs,
  `ik2/single-lab-header`, `ik2/lab-tool` at wide, `post-content`,
  `ik2/single-lab-notes`.
- `patterns/single-lab-header.php` (title, excerpt, source link when set) and
  `patterns/single-lab-notes.php` ("Related notes", from
  `single-project-writing.php`). Reuse the existing `ik-related__*` and list
  classes. Rename them to something neutral only if that is a small diff.
- `inc/assets.php`: add `lab` to `ARTICLE_POST_TYPES`. If the lightbox or image
  sizing misbehaves around the frame, take it back out and enqueue the
  `single` section alone.
- `inc/breadcrumbs.php`: Home > Lab > name.

### 6. Backlink on posts (`feat(single)`)

- `patterns/single-article-lab.php`, from `single-article-project.php`, placed
  beside it in `templates/single.html`.

### 7. Index (`feat(lab)`)

- `blocks/lab-archive/` (`block.json`, `render.php`, `style.css`), from
  `projects-archive`: one row per entry with title, excerpt and whether it has
  a tool.
- `templates/page-lab.html`, from `page-projects.html`, with its intro inline.
- `inc/cli/setup/class-pages-step.php`: add the Lab page for fresh installs.
- Header nav link: not in this change. Ivan wants it once there are a few
  entries.

### 8. Docs (`docs`)

- `CLAUDE.md`: one paragraph on the lab shell and where tools live.
- ADR `0005-lab-shell-and-separate-instance.md`: the iframe decision and why
  same-origin hosting was turned down.

## Verification

- `composer quality`, `composer test`, `pnpm lint`, `node --test
  tests/lab-tool-rules.test.mjs`, `pnpm test:csp`.
- A stand-in tool: one static HTML file served from the scratchpad on a
  localhost port that matches `IK2_LAB_ORIGIN`, posting a resize message on
  load and on a button press. With playwright-cli, check on a published test
  entry: the frame loads, grows on the message, ignores the same message sent
  from the parent page's own window, has an accessible name, and takes
  keyboard focus in order.
- Second entry with no URL, third with an off-origin URL: neither prints a
  frame.
- Tick the entry on a fixture post: the post shows under "Related notes" and
  the post shows the backlink. Draft the entry: both disappear, the term stays.
- `/lab/` lists the entries, and `/sitemap_index.xml` has a lab sitemap.
- Lighthouse on the entry page: no new accessibility or CSP findings, CLS
  unchanged from a project page.

## After release (content work on prod, by hand)

- Create the `lab` page with slug `lab` in wp-admin (releases don't write page
  content), then its Yoast fields and social card through `wp_draft.py --page`.
- The first real entry waits for the lab instance to exist.
