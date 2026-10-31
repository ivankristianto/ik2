---
name: blog-seo
description: Fill in the Yoast SEO fields for an ivankristianto.com post (focus keyphrase, SEO title, meta description, featured image alt) so it scores well on-page, then audit the rendered page or preview for on-page SEO best practice (title and description, headings, keyphrase placement, links, images, Open Graph, schema, canonical, robots). Works on local drafts, WordPress drafts and published posts over REST through 1Password; Chrome only reads Yoast's scores. Use when Ivan asks to fill in Yoast, improve or check a post's SEO, audit on-page SEO, write a meta description or SEO title, or types /blog-seo. Optional step after blog-draft and blog-feature-image.
user-invocable: true
---

# Blog SEO

Input is one post: a local draft (`.claude/blog/drafts/<slug>.md`), an edit link (`post.php?post=ID&action=edit`), a post ID, a published URL, or a slug. Output is the Yoast fields in `.claude/blog/drafts/<slug>/seo.txt` and on the post, an audit, and a short report. Saving never changes the post's status: drafts stay drafts. On a published post the new fields go live on save, so say that in the report.

Fields are read and written over REST through 1Password (`.claude/skills/blog-wp-draft/scripts/wp_draft.py`), not in the editor. Chrome is only for Yoast's computed scores (step 6), which exist nowhere else.

## 1. Read the post

- **Local draft, not yet in WordPress:** read the Markdown. The fields go into `seo.txt`, and blog-wp-draft sets them when it creates the post.
- **A post in WordPress:** fetch it with the app password from 1Password. `op run` masks the username in stdout, so write to a file:
  ```bash
  op run --env-file=.env.op.local -- sh -c 'curl -s -A Mozilla/5.0 -u "$IK2_PROD_USER:$IK2_PROD_APP_PASSWORD" "https://www.ivankristianto.com/wp-json/wp/v2/posts/<ID>?context=edit&_fields=id,status,slug,title,excerpt,content,featured_media,meta" -o <scratchpad>/post.json'
  ```
  `meta` holds the current `_yoast_wpseo_focuskw`, `_yoast_wpseo_title` and `_yoast_wpseo_metadesc`. The featured image alt is at `/wp/v2/media/<featured_media>?_fields=alt_text`.

Also read how Ivan wrote the fields on his recent posts, and match them:

```bash
curl -s -A Mozilla/5.0 'https://www.ivankristianto.com/wp-json/wp/v2/posts?per_page=5&_fields=title,yoast_head_json.title,yoast_head_json.description'
```

## 2. Audit the page before changing anything

`audit.py` checks the rendered page against on-page best practice and prints pass, warn or fail per check. Run it with no keyphrase first, for a baseline.

- **Published post:**
  ```bash
  python3 -I .claude/skills/blog-seo/audit.py <url> --json <scratchpad>/audit-before.json
  ```
- **Local draft or WordPress draft:** build a stand-in page from the Markdown, then audit it:
  ```bash
  python3 -I .claude/skills/blog-wp-draft/scripts/md2blocks.py .claude/blog/drafts/<slug>.md --page <scratchpad>/page.html > /dev/null
  python3 -I .claude/skills/blog-seo/audit.py <scratchpad>/page.html --preview
  ```
  The page uses the draft's `seo.txt` and `alt.txt` when they exist. Open Graph, Twitter and the Article schema always fail on it, because Yoast adds them on the real site, so ignore those three there. For a WordPress draft whose text differs from the local file, audit Ivan's preview instead: open `https://www.ivankristianto.com/?p=<ID>&preview=true` in Chrome, run `extract.js`, save the result to `<scratchpad>/facts.json`, and run `audit.py --facts <scratchpad>/facts.json --preview`.

What it checks: one `<title>` of 30 to 60 characters, the `- ivan` sitename template, a 120 to 156 character meta description, canonical, robots, one H1, no skipped heading levels, word count, alt text on body and featured images, internal and outbound links, empty and generic anchor text ("click here"), Open Graph and Twitter tags, an Article node in the JSON-LD with headline, date, author and image, `lang`, viewport, and slug length. With `--keyphrase` it adds placement checks: title (and how early), meta description, H1, first paragraph, an H2 or H3, slug, and density.

Some failures belong to the theme or Yoast, not to the post: a duplicate `<title>`, a missing Open Graph tag, broken JSON-LD. Report those as site issues with the check that caught them. Don't try to fix them from the post.

## 3. Choose the focus keyphrase

The phrase a developer would type to find this post, one to three words, and one the post already uses:

- It appears in the H1 or slug, in the first paragraph, and at least three times in the body. Count it with `--keyphrase` before you commit to it.
- Some posts carry no such phrase: an intro that never names the topic, a product named twice in four thousand words. Still pick what a reader would search, then put the missing placements in the suggestions as exact rewrites (a new first paragraph, renamed headings, a sentence or two that name the thing). Don't settle for a phrase nobody searches because it happens to be in the intro.
- Prefer the specific thing (`WebMCP`, `COOKIE_DOMAIN`, `PHP CVE`) over a pairing the text never says. "WebMCP WordPress" scored worse than "WebMCP" on the WebMCP post because the exact phrase appears nowhere.
- When two candidates are close, run both through `--keyphrase` and, if Ivan wants the editor's verdict, through Yoast (step 6) and keep the better one. Say in the report which you rejected and why.

## 4. Write the fields

- **SEO title**: 60 characters or fewer, keyphrase in the first half, and ending in ` - Ivan Kristianto`, written by hand. The Yoast template gives `- ivan`, the site name. It may differ from the H1: say what the post answers rather than repeating it.
- **Meta description**: 120 to 150 characters. Yoast counts the date it shows before a post's description against its 156 limit, so stay under 150. Contains the keyphrase once, says what the reader gets, first person where it fits ("I added..."). One or two sentences.
- **Featured image alt**: change it only when the keyphrase is literally on the image and the current alt leaves it out, and only by adding words that stay true to the image.
- **Social fields** (Facebook, X): leave them empty. They fall back to the SEO title and description.

Voice rules from `blog-draft` apply: no hype words, no em or en dashes, no exclamation marks, straight quotes, every claim true to the post. No client names, private repos or ticket keys, even if Ivan's prompt includes them; say in the report that you left them out. Run `bash .claude/skills/blog-ideas/leak-check.sh <file>` on the fields before saving.

## 5. Save the fields

Write them to `.claude/blog/drafts/<slug>/seo.txt`, the file blog-wp-draft reads:

```
Focus keyphrase: <keyphrase>
SEO title (<n>): <seo title>
Meta description (<n>): <meta description>
```

Run `bash .claude/skills/blog-ideas/leak-check.sh` on it. Then:

- **No WordPress post yet:** stop here. blog-wp-draft sets the fields and the alt text when it creates the draft.
- **A post exists:** dry-run, then apply. Verify with the `checks` in the report: each meta key, Yoast's rendered title and description, and an unchanged status.
  ```bash
  op run --env-file=.env.op.local -- python3 -I .claude/skills/blog-wp-draft/scripts/wp_draft.py seo <ID> .claude/blog/drafts/<slug>/seo.txt --report <scratchpad>/seo-apply.json --apply
  cat <scratchpad>/seo-apply.json
  ```
  On a published post (`"live": true`), say before applying that the fields go live.

When the featured image alt needs the keyphrase (step 4), edit `alt.txt` and run blog-wp-draft's `push`. It sets the alt text on the existing media.

Then re-run the audit from step 2 with `--keyphrase`.

## 6. Yoast's scores (Chrome, optional)

Yoast only computes its SEO and readability scores in the editor; REST and the `yoast-seo/*` abilities return `na` until then. Ivan usually opens the edit link and looks. When he asks you to, open it in Chrome with claude-in-chrome, wait for the editor, and read them without saving:

```js
await new Promise(r => setTimeout(r, 3000));  // Yoast re-runs its analysis in a worker.
const y = wp.data.select('yoast-seo/editor');
const res = y.getResultsForFocusKeyword().results;
JSON.stringify({ dirty: wp.data.select('core/editor').isEditedPostDirty(),
  seo: document.getElementById('yoast_wpseo_linkdex').value,
  readability: document.getElementById('yoast_wpseo_content_score').value,
  notGreen: res.filter(r => r.score < 7).map(r => `${r._identifier}:${r.score}`) })
```

Aim for an SEO score of 70 or more (green), and fix every field-level item that isn't green: title length or keyphrase position, description length or keyphrase, keyphrase length. Fix those in `seo.txt` and re-run step 5; don't type into the editor. If `dirty` is true, Ivan has unsaved edits in that tab: touch nothing.

Body-level items are Ivan's writing: keyphrase in a subheading, in the intro, density, competing links, image alt in the body. List them in the report as suggestions with the exact wording you'd use, for example renaming "Three tools over the articles I already publish" to "Three WebMCP tools over the articles I already publish". Don't edit the body unless he says yes. `imageKeyphrase` only reads images in the content, so it stays amber for posts whose only image is the featured one. `functionWordsInKeyphrase:0` is a placeholder; ignore it.

## 7. Report

- The fields: before and after, with character counts, and the keyphrase you chose over which other one.
- Yoast's SEO and readability scores, when step 6 ran; otherwise say Ivan can see them on the edit link.
- The audit as a short list of what isn't passing, split into post fixes Ivan can make (with exact suggested wording) and site issues (theme or Yoast).
- Whether the post is live, and the full edit and post URLs.

## Eval runs

When the prompt points at a fixture in `evals/files/pages/` instead of a live post, there's no WordPress to write to. Run `audit.py <fixture> --keyphrase <chosen> --json outputs/audit.json`, write `outputs/seo.json` with `focus_keyphrase`, `seo_title`, `meta_description` and `suggestions` (a list of strings), and put the report in `outputs/response.md`. Don't open Chrome or fetch anything.
