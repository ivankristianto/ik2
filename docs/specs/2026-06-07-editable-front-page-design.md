# Editable front page — static Home page owns the homepage sections

## Goal

The homepage is currently locked to six hardcoded dynamic blocks in
`templates/front-page.html`. Early on there is no data for notes, projects,
or speaking, so those sections render hollow. Move section control into a
real "Home" page: the template renders `core/post-content`, and the page
editor decides which `ik2/home-*` blocks appear and in what order.
`wp ik2 setup` provisions the page and the reading options.

## Template

`wp-content/themes/ik2/templates/front-page.html` keeps the chrome and
hands the middle to the page:

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"default"}} -->
<main class="wp-block-group">
	<!-- wp:post-content /-->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

No `core/post-title` — the page stays a pure section canvas. With
`show_on_front = page`, `front-page.html` still wins the template
hierarchy and renders the Home page's content.

## Patterns (theme)

All six homepage sections become individually re-insertable, plus a
full-page composition:

1. **New `patterns/home-projects-preview.php`** — fills the existing gap
   (the other five sections already have thin-wrapper patterns).
   Header: `Title: Home — Projects Preview`, `Slug:
   ik2/home-projects-preview`, `Categories: ik2-home`. Body:
   `<!-- wp:ik2/home-projects-preview /-->`.
2. **New `patterns/home-page.php`** — the full six-section composition in
   template order (hero, featured topics, evergreen guides, latest notes,
   projects preview, speaking preview). Header: `Title: Home — Full page`,
   `Slug: ik2/home-page`, `Categories: ik2-home`,
   `Block Types: core/post-content`, `Post Types: page`. The block-types /
   post-types pairing makes WordPress offer it as a starter pattern when
   creating a page, and it stays available in the inserter for re-adding
   sections later.

## CLI provisioning (ik2 plugin)

1. **New `setup/class-home-page-step.php`** (`Home_Page_Step`), modeled on
   `Privacy_Page_Step`:
   - Resolve the Home page: `get_page_by_path( 'home', OBJECT, 'page' )`.
   - Missing → `wp_insert_post` a published "Home" page (slug `home`) whose
     content is the registered `ik2/home-page` pattern's content, read from
     `WP_Block_Patterns_Registry::get_instance()->get_registered( 'ik2/home-page' )`
     — single source of truth, no duplicated markup in the step. If the
     pattern is not registered (theme inactive), fail the check with a
     note pointing at the theme step.
   - Existing page → never touch its content (it is editorial); only
     converge the options.
   - Converge `page_on_front = <id>` and `show_on_front = page` with the
     usual already-set / set notes. A `page_on_front` pointing at a
     different published page fails the check (✗) with a note to re-run
     with `--force`; `show_on_front` is only flipped to `page` once
     `page_on_front` actually points at the Home page, so a bare run
     never redirects the homepage to a page the step did not choose.
   - `page_for_posts` stays unset — `/articles` is a page with its own
     template (`page-articles.html`), there is no separate posts page.
2. **`Reading_Step`** — drop the `show_on_front => 'posts'` pin and the
   now-stale docblock sentence about `front-page.html` owning the
   homepage. Keep `posts_per_page = 9` and `blog_public = 1`.
3. **`class-setup-command.php`** — register `Home_Page_Step` (step key
   `home-page`) after the theme step (the pattern must be registered
   before the step reads it) and adjacent to the other page/option steps.

## Tests

`tests/home-patterns-structure.test.mjs` currently asserts
`front-page.html` renders each `ik2/home-*` block directly. Rewrite:

- `front-page.html` must contain `<!-- wp:post-content` and must NOT
  contain any `ik2/home-*` block.
- Add `home-projects-preview.php` to the pattern-file list (six thin
  wrappers, existing assertions apply).
- New assertions for `patterns/home-page.php`: contains all six
  `ik2/home-*` blocks in template order, plus
  `Block Types: core/post-content`.

## Behavior notes

- Running `ik2 setup` on the current dev site creates the Home page with
  all six sections and flips the options; the rendered homepage is
  identical until the page is edited.
- The user then deletes the notes / projects / speaking blocks in the
  page editor and re-adds them later from the `ik2-home` pattern category
  (or re-inserts the full `Home — Full page` pattern).
- Each `ik2/home-*` block has `"multiple": false`, so a section cannot be
  accidentally duplicated on the page.

## Conventions

- Named methods / named callables only (project PHP convention).
- WPCS + PHPStan level 6 clean (`composer quality`).

## Verification

- `node tests/home-patterns-structure.test.mjs` passes (plain top-level
  assertions; not wired into package.json or CI).
- `composer dev:wp:cmd -- ik2 setup` on the dev stack: Home page created,
  `show_on_front` / `page_on_front` set; re-run is all ✓ already-set.
- Homepage renders identically before/after; deleting a block in the page
  editor removes that section from the front end.
- `composer quality`.
