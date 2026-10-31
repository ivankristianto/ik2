---
name: blog-refurbish
description: Give an old ivankristianto.com post a second life without rewriting it. Researches what changed since it was published, writes a dated first-person "Updated" callout on top of the untouched original, makes and uploads a new feature card, fills in Yoast, then updates the live post after Ivan approves. Chains blog-research, blog-draft, blog-feature-image and blog-seo with refresh-specific rules. Use when Ivan asks to refurbish, refresh, revamp or update an old post, add an update section, pastes an old post URL and says "update it", or types /blog-refurbish. For a new post, use blog-draft instead.
user-invocable: true
---

# Blog refurbish: a dated update on top, the old post untouched

Input is one or more published posts: URLs, slugs, post IDs or edit links. Several posts run as one batch: research them in parallel, ask Ivan once, review once, apply once. Ivan may drop steps per post ("SEO only for this one"); do only what's left.

Output, per post:

- A dated callout prepended to the post body. The original text below it stays byte-for-byte the same.
- A new featured image, uploaded with its alt text. The old image stays in the media library.
- New Yoast fields.
- A final report covering every page.

Precedents to read before starting:

- Live examples: https://www.ivankristianto.com/seoquake-seo-user-friendly-tools-a-firefox-extension/ and https://www.ivankristianto.com/free-php-obfuscator/
- Their files: `.claude/blog/drafts/seoquake-seo-user-friendly-tools-a-firefox-extension/` and `.claude/blog/research/seoquake-seo-user-friendly-tools-a-firefox-extension.md`

## Files

The slug is the post's own WordPress slug. Everything goes in gitignored folders, never anywhere else in the repo:

```
.claude/blog/research/<slug>.md
.claude/blog/drafts/<slug>/update.md      # the callout as Markdown
.claude/blog/drafts/<slug>/callout.html   # block markup, from scripts/callout.py
.claude/blog/drafts/<slug>/feature.html|png|webp, card.css, tokens.css, alt.txt
.claude/blog/drafts/<slug>/seo.txt        # keyphrase:, title:, description:
```

Use the scratchpad for scratch files (post text, GSC exports, backups).

## 1. Read the post and its search data

Read the post from bash, not Chrome:

```bash
curl -s -A 'Mozilla/5.0' 'https://www.ivankristianto.com/wp-json/wp/v2/posts?slug=<slug>&_fields=id,date,link,title,content,featured_media,yoast_head_json.robots'
```

Save its plain text to the scratchpad. `yoast_head_json.robots.index` reads `noindex` when the post is hidden from search.

Pull the queries that bring people to the post. Use blog-analytics (see "Run it" in its SKILL.md for loading the key per run): `gsc --dimensions query --filter page:contains:/<slug>/ --days 90`. The queries tell you what readers came for today, and they decide the keyphrase. If the post has no impressions, say so and keep going.

Stop and ask Ivan when either of these is true:

- The raw content already contains `is-style-callout-updated`. Ask whether to replace that callout or stack a new one above it.
- The post is `noindex`. Ask whether it should be indexed again.

## 2. Research (blog-research, with these changes)

Follow blog-research's verification rules: primary sources, re-checked today, every claim marked `verified`, `sourced` or `unverified`, and `leak-check.sh` before reporting. Override the rest:

- The source is the live post, not a session. There is no firsthand story to recover.
- Find out what is true now about each thing the post recommends:
  - Is it still alive, and who owns it?
  - What is the current version, and when did it ship?
  - What died, with a shutdown date and source for each?
  - Which links in the post are dead or now point somewhere else? Check them with `curl -sI`. Never link a domain that now points to an unrelated site.
  - What would a working engineer use instead?
- Research file sections:
  - Thesis
  - Audience (who lands here from those queries)
  - What changed since <year>
  - Claims table
  - Related
  - Keyphrase candidates, each tied to the GSC query volume behind it
  - Options for Ivan: three or four one-line positions he might take today
  - Leave out
- For a batch, run one background agent per post. Each agent writes its own file and never asks Ivan anything. You ask, in step 3.

## 3. Ask Ivan where he stands today

The callout is his opinion, so never invent it. Ask in one `AskUserQuestion` batch, with one question per post (up to four per batch). Each question:

- opens with two lines on what changed;
- offers the research's options as answers;
- lets him type his own.

Skip a post's question when his message already says what he thinks. The obfuscator refresh came with his opinion written out, and asking again would have wasted his time.

Append his answers to the research file:

```
## Questions for Ivan

Asked <YYYY-MM-DD>.

- Where he stands today: <answer> (from Ivan)
```

## 4. Write the callout (blog-draft, with these changes)

Read `.claude/skills/blog-draft/references/voice.md` and run `recent-posts.py`, as blog-draft says. The output is `drafts/<slug>/update.md`, not a post.

**Format**

- First line: `Updated <Month D, YYYY>` (`Updated October 30, 2026`). This matches the six live callouts; don't switch to ISO here.
- 150 to 250 words, first person, in paragraphs, plus a `- ` list only when items are truly parallel.
- No frontmatter, no headings, no Sources list.
- Links inline, full `https://` URLs. `callout.py` only understands `[text](https://...)`, backticks and `- ` lists.

**Content**

- Open with when he wrote it and what the reader who searched for it should know now.
- Then say what changed: versions, shutdowns and dead links, with dates.
- Then say what he uses or would do today, from his answer.
- End with one plain line for the reader who still wants the old thing.
- Say flatly what in the old text is wrong ("The word "anonymously" below is wrong"), without apologising.
- Every number has to be in the research file. Write volatile facts as "as I write this" or with the month.

Run blog-draft's checks on `update.md`, which must all come back empty except `wc -w`:

- the dash grep
- the banned-word grep
- the giveaway-phrase grep
- the placeholder grep
- `leak-check.sh`
- `wc -w`

Then:

```bash
python3 -I .claude/skills/blog-refurbish/scripts/callout.py .claude/blog/drafts/<slug>/update.md
```

## 5. Make the feature card (blog-feature-image, with these changes)

Follow blog-feature-image completely: the punch line, real evidence, patterns A/B/C, render, the three looks, and alt text with leak-check.

- The card is about the update, not the 2011 post. Good evidence for a refresh:
  - dead metrics struck out with the year each ended;
  - an old command struck out next to the current one;
  - old version vs current version;
  - three current numbers.
- Keep the files as `feature.html`, `feature.png` and `feature.webp`.
- Upload under the name `<keyphrase-slug>-<year>-update.<ext>`, for example `seoquake-2026-update.webp`. That becomes the media filename, so name it in the review.
- Unlike blog-feature-image, this skill uploads, but only in step 8.

## 6. Write the SEO fields (blog-seo steps 2 to 4)

Run the baseline `audit.py` on the live URL. Choose the keyphrase from the GSC queries in step 1 (blog-seo step 3), then write the fields to `seo.txt`. These rules apply on top of blog-seo:

- **Title:** the keyphrase goes first. Yoast marked titles down when it didn't. A year in the title helps on a refresh ("SeoQuake for Firefox and Chrome in 2026").
- **Meta description:** keep it at about 135 characters or fewer. Old posts show their date in search results, and Yoast counts that date against the limit.
- **`introductionKeyword`:** expect it to stay amber, because Yoast reads the callout title as the intro. List it in the report and don't fight it.

## 7. Review gate

Nothing goes live before Ivan says yes. Show one table, one row per post:

- the post link;
- a one-line summary of the callout, with a `file:///` link to `update.md`;
- a `file:///` link to the card PNG;
- the alt text;
- the keyphrase, title and description, with character counts.

Under the table, flag anything sourced from only one place, or anything you doubted. Ivan may approve all, approve some, or ask for edits. Re-run the checks after any edit.

## 8. Apply

Do this in Ivan's logged-in Chrome, in a wp-admin tab on https://www.ivankristianto.com/wp-admin/. The code is in `references/browser.md`. Do these steps in order, and stop on any check that fails:

1. **Back up.** Read `content.raw` for every post and keep it in the page. Also copy it to the scratchpad if the Chrome tool lets you (see the gotcha in `references/browser.md`).
2. **Prepend the callout.** Refuse if the raw changed since the backup or already has a callout. Save `callout + old raw`, then check that the saved raw equals exactly that. Do one post per call, so a failure tells you which post failed.
3. **Upload the card.** Inject a file input, then `file_upload` the WebP (or the PNG when it's smaller). POST the file to `/wp/v2/media` with its alt text, set `featured_media`, check both, and remove the input.
4. **Save the Yoast fields.** Load each post's edit screen fresh, after step 3. An edit tab opened earlier (blog-seo step 1 opens one) still holds the old content, and saving it would write that content back over the callout. Before touching Yoast, confirm three things:
   - the editor isn't dirty;
   - `getEditedPostContent()` contains `callout-updated`;
   - `featured_media` is the new media ID.

   Stop if any fails. Otherwise follow blog-seo steps 5 and 6, fix every field-level item that isn't green, and save.
5. **Remove noindex,** only where Ivan approved it in step 1: `setNoIndex('0')` in the same save.

## 9. Verify live and report

For each post, fetch the plain URL with no cache-buster: `curl -s -A 'Mozilla/5.0' <url>`. The page must show:

- the callout;
- the new `og:image`;
- the new `<title>` and description;
- `index` in robots.

Then run `audit.py --keyphrase` again.

Report every updated page in one table:

- the post URL and the edit URL (`https://www.ivankristianto.com/wp-admin/post.php?post=<ID>&action=edit`);
- the keyphrase;
- the SEO title, with its character count before and after;
- Yoast's SEO and readability scores;
- the new media ID and the old one.

Below the table, add:

- What changed on each post.
- Anything still amber, with exact rewrites Ivan could make to the old body (renamed headings, a sentence naming the keyphrase). Don't make them without his yes.
- Old images kept in the media library.

Update `.claude/memory/project-old-post-refresh.md` with the posts refreshed and the date, plus any new gotcha. Leave the rest of the repo untouched: `git status` should show only that memory file.

## Eval runs

When the prompt points at fixtures in `evals/files/` instead of a live post, run offline up to the review gate:

- **Inputs from fixtures**
  - Read the post from `evals/files/posts/<slug>.json`. Its `content_raw` is the post's `content.raw`, and its `robots` is the live robots value.
  - Read the queries from `evals/files/gsc/<slug>.json`.
  - Don't run blog-analytics, open Chrome or write to WordPress. Fetching public web pages for research is fine.
- **Questions**
  - `AskUserQuestion` isn't available.
  - Wherever this skill would ask Ivan something, write the question and its options in `outputs/response.md` and stop.
  - Treat a stance stated in the eval prompt as his answer.
- **Outputs.** Write under `outputs/`, not `.claude/blog/`:
  - the research file: `outputs/research/<slug>.md`;
  - the drafts: `outputs/drafts/<slug>/`, with the same file names as a real run;
  - instead of step 8, `outputs/drafts/<slug>/content.html`: the exact content step 8.2 would save;
  - the step 7 review: `outputs/response.md`.

## Writing rules

Ivan's global style rules apply to the callout, the card, the alt text and the SEO fields:

- no em or en dashes;
- no hype words;
- no "it's not X, it's Y";
- straight quotes;
- no exclamation marks;
- no client names.
