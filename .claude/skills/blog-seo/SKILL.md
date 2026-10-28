---
name: blog-seo
description: Fill in the Yoast SEO fields for an ivankristianto.com post (focus keyphrase, SEO title, meta description, featured image alt) so it scores well on-page, then audit the rendered page or preview for on-page SEO best practice (title and description, headings, keyphrase placement, links, images, Open Graph, schema, canonical, robots). Works on drafts and published posts through Ivan's logged-in Chrome. Use when Ivan asks to fill in Yoast, improve or check a post's SEO, audit on-page SEO, write a meta description or SEO title, or types /blog-seo. Optional step after blog-draft and blog-feature-image.
user-invocable: true
---

# Blog SEO

Input is one post: an edit link (`post.php?post=ID&action=edit`), a post ID, a published URL, or a slug. Output is the Yoast fields saved on that post, an audit of the rendered page, and a short report. Saving never changes the post's status: drafts stay drafts. On a published post the new fields go live on save, so say that in the report.

## 1. Read the post

Open the edit link in Chrome with claude-in-chrome (Ivan is logged in; there's no `.env` app password, see `.claude/memory/project-prod-page-content-via-rest.md`). From the editor page:

```js
const ed = wp.data.select('core/editor'), y = wp.data.select('yoast-seo/editor');
JSON.stringify({ status: ed.getEditedPostAttribute('status'), title: ed.getEditedPostAttribute('title'),
  slug: ed.getEditedPostAttribute('slug'), excerpt: ed.getEditedPostAttribute('excerpt'),
  featured: ed.getEditedPostAttribute('featured_media'), dirty: ed.isEditedPostDirty(),
  yoast: { kw: y.getFocusKeyphrase(), title: y.getSeoTitle(), desc: y.getDescription() } })
```

If `dirty` is true, Ivan has unsaved edits in that tab. Stop and ask before touching anything.

Read the text you will summarize: headings and paragraphs from `wp.data.select('core/block-editor').getBlocks()`. Strip URLs from what you return; the browser tool refuses output that carries query strings. Featured image alt: `wp.apiFetch({path: '/wp/v2/media/<id>?context=edit&_fields=alt_text'})`.

Also read how Ivan wrote the fields on his recent posts, and match them:

```js
wp.apiFetch({path: '/wp/v2/posts?per_page=5&status=publish&_fields=title,yoast_head_json'})
```

## 2. Audit the page before changing anything

`audit.py` checks the rendered page against on-page best practice and prints pass, warn or fail per check. Run it with no keyphrase first, for a baseline.

- Published post: `python3 -I .claude/skills/blog-seo/audit.py <url> --json <scratchpad>/audit-before.json`
- Draft: open `https://www.ivankristianto.com/?p=<ID>&preview=true` in Chrome, run the contents of `extract.js` with the javascript tool, save the returned string to `<scratchpad>/facts.json`, then `audit.py --facts <scratchpad>/facts.json --preview`. `--preview` stops the noindex that every preview carries from failing.

What it checks: one `<title>` of 30 to 60 characters, the `- ivan` sitename template, a 120 to 156 character meta description, canonical, robots, one H1, no skipped heading levels, word count, alt text on body and featured images, internal and outbound links, empty and generic anchor text ("click here"), Open Graph and Twitter tags, an Article node in the JSON-LD with headline, date, author and image, `lang`, viewport, and slug length. With `--keyphrase` it adds placement checks: title (and how early), meta description, H1, first paragraph, an H2 or H3, slug, and density.

Some failures belong to the theme or Yoast, not to the post: a duplicate `<title>`, a missing Open Graph tag, broken JSON-LD. Report those as site issues with the check that caught them. Don't try to fix them from the post editor.

## 3. Choose the focus keyphrase

The phrase a developer would type to find this post, one to three words, and one the post already uses:

- It appears in the H1 or slug, in the first paragraph, and at least three times in the body. Count it with `--keyphrase` before you commit to it.
- Some posts carry no such phrase: an intro that never names the topic, a product named twice in four thousand words. Still pick what a reader would search, then put the missing placements in the suggestions as exact rewrites (a new first paragraph, renamed headings, a sentence or two that name the thing). Don't settle for a phrase nobody searches because it happens to be in the intro.
- Prefer the specific thing (`WebMCP`, `COOKIE_DOMAIN`, `PHP CVE`) over a pairing the text never says. "WebMCP WordPress" scored worse than "WebMCP" on the WebMCP post because the exact phrase appears nowhere.
- When two candidates are close, run both through `--keyphrase` and through Yoast (step 5) and keep the better one. Say in the report which you rejected and why.

## 4. Write the fields

- **SEO title**: 60 characters or fewer, keyphrase in the first half, and ending in ` - Ivan Kristianto`, written by hand. The Yoast template gives `- ivan`, the site name. It may differ from the H1: say what the post answers rather than repeating it.
- **Meta description**: 120 to 150 characters. Yoast counts the date it shows before a post's description against its 156 limit, so stay under 150. Contains the keyphrase once, says what the reader gets, first person where it fits ("I added..."). One or two sentences.
- **Featured image alt**: change it only when the keyphrase is literally on the image and the current alt leaves it out, and only by adding words that stay true to the image.
- **Social fields** (Facebook, X): leave them empty. They fall back to the SEO title and description.

Voice rules from `blog-draft` apply: no hype words, no em or en dashes, no exclamation marks, straight quotes, every claim true to the post. No client names, private repos or ticket keys, even if Ivan's prompt includes them; say in the report that you left them out. Run `bash .claude/skills/blog-ideas/leak-check.sh <file>` on the fields before saving.

## 5. Apply in the editor and read Yoast's score

```js
const d = wp.data.dispatch('yoast-seo/editor');
d.setFocusKeyword('<keyphrase>');
d.updateData({ title: '<seo title>', description: '<meta description>' });
await new Promise(r => setTimeout(r, 3000));  // Yoast re-runs its analysis in a worker.
const res = wp.data.select('yoast-seo/editor').getResultsForFocusKeyword().results;
JSON.stringify({ seo: document.getElementById('yoast_wpseo_linkdex').value,
  readability: document.getElementById('yoast_wpseo_content_score').value,
  notGreen: res.filter(r => r.score < 7).map(r => `${r._identifier}:${r.score}`) })
```

Aim for an SEO score of 70 or more (green), and fix every field-level item that isn't green: title length or keyphrase position, description length or keyphrase, keyphrase length. Body-level items are Ivan's writing: keyphrase in a subheading, in the intro, density, competing links, image alt in the body. List them in the report as suggestions with the exact wording you'd use, for example renaming "Three tools over the articles I already publish" to "Three WebMCP tools over the articles I already publish". Don't edit the body unless he says yes. `imageKeyphrase` only reads images in the content, so it stays amber for posts whose only image is the featured one. `functionWordsInKeyphrase:0` is a placeholder; ignore it.

Featured alt goes through REST, not the editor: `wp.apiFetch({path: '/wp/v2/media/<id>', method: 'POST', data: {alt_text}})`.

## 6. Save and verify

```js
await wp.data.dispatch('core/editor').savePost();
// Yoast's fields ride on the meta box save; wait for it, not only for the post.
await new Promise(r => { const t = () => (wp.data.select('core/editor').isSavingPost() ||
  wp.data.select('core/edit-post').isSavingMetaBoxes()) ? setTimeout(t, 300) : r(); setTimeout(t, 800); });
```

Then check that it stuck:

- `wp.apiFetch({path: '/wp/v2/posts/<ID>?context=edit&_fields=status,yoast_head_json'})`: the status hasn't changed, and `yoast_head_json.title` and `.description` are the new ones.
- The focus keyphrase isn't in REST. Fetch the edit page HTML and read `yoast_wpseo_focuskw`'s value.
- Re-run the audit from step 2 with `--keyphrase` on the live page or a fresh preview.

## 7. Report

- The fields: before and after, with character counts, and the keyphrase you chose over which other one.
- Yoast's SEO and readability scores after saving.
- The audit as a short list of what isn't passing, split into post fixes Ivan can make (with exact suggested wording) and site issues (theme or Yoast).
- Whether the post is live, and the full edit and post URLs.

## Eval runs

When the prompt points at a fixture in `evals/files/pages/` instead of a live post, there's no WordPress to write to. Run `audit.py <fixture> --keyphrase <chosen> --json outputs/audit.json`, write `outputs/seo.json` with `focus_keyphrase`, `seo_title`, `meta_description` and `suggestions` (a list of strings), and put the report in `outputs/response.md`. Don't open Chrome or fetch anything.
