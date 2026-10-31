---
name: blog-review
description: Grade an ivankristianto.com post or draft like an eval. Read it once as a reader, then audit structure, check every factual claim against its source, confirm the sources say what the post cites them for, and catch hallucinated specifics, AI slop, em dashes and text that sounds generated. Weighs most heavily whether Ivan could reread it in a year and remember what he did and learned. Outputs a letter grade, a 0 to 100 score, per-item evidence and exact rewrites. Read-only; never edits, saves or publishes the post. Use when Ivan asks to review, grade, audit, fact-check or proofread a post or draft, pastes a post.php edit link and asks what's wrong with it, or types /blog-review. Runs after blog-draft and before blog-seo.
user-invocable: true
---

# Blog review

Input: an edit link (`post.php?post=ID&action=edit`), a preview link (`?p=ID`), a post ID, a published URL or slug, or a local draft in `.claude/blog/drafts/`. Output: `.claude/blog/reviews/<slug>-<YYYY-MM-DD>.{json,md}` (gitignored) and a short chat report. You never change the post. No editor saves, no REST writes, no Yoast. SEO fields belong to `blog-seo`.

You are a grader, not a co-author. Every verdict quotes the post. Every miss gets the exact sentence you'd write instead, or, when only Ivan knows the fact it needs, a question for him. Never write his motive, moment or result for him, not even as an example. Don't round up because the post is tidy.

## 1. Get the text

Work in `<scratchpad>/review/`. Save the post as Markdown at `<slug>.md`, starting with a `# Title` line. Use `##` headings, fenced code blocks, `1.` for ordered steps, `-` for bullets, and `[n]` citation markers. End with a `## Sources` list of `- [n] https://...` lines.

Try these in order and use the first that works:

1. **Local draft.** Copy the file. Skip ahead to step 2.
2. **REST.** Run it under `op run` so drafts are readable too. The app password comes from `op://Agents/ik2 prod`:
   ```bash
   op run --env-file=.env.op.local -- python3 -I .claude/skills/blog-review/fetch.py <input> <scratchpad>/review/<slug>.md
   ```
   `op run` masks the username in stdout, so links in the printed metadata look mangled. Read `<slug>.meta.json` beside the Markdown instead. Exit 3 means REST can't read it: a 401 means the app password is stale, so ask Ivan to refresh it. Use Chrome only when he says to.
3. **Chrome**, only when Ivan says to (load the `claude-in-chrome` skill first, and open a new tab). Ivan's logged-in session reads drafts.
   - On the edit link, run `wp.apiFetch({path: '/wp/v2/posts/<ID>?context=edit&_fields=status,title,slug,modified,categories,tags'})`. Also read `wp.data.select('core/editor').isEditedPostDirty()`. If it's dirty, the review covers the last saved version, not the open tab. Say so in the report.
   - Open `https://www.ivankristianto.com/?p=<ID>&preview=true`. Take the text from `get_page_text` and the structure from running `outline.js` with `javascript_tool`.
   - Rebuild the Markdown from both. Paste the `get_page_text` text verbatim and only add structure: `##` headings, code fences, `1.` and `-` list markers, and `[n]` markers. The theme renders citations as bare superscript digits ("fixture.7", "1,3"), so turn them back into `[7]` and `[1][3]`. Don't fix a word, a quote or a dash while you do it.
   - Compare `outline.js`'s `em`, `en`, `bang` and `words` with what `check.py` reports in step 3 (W1, W3b, `words`). If they disagree by more than a few words, your copy drifted, so redo it.
   - Don't try to pull the full body through `javascript_tool`. It came back `[BLOCKED: Cookie/query string data]` on a post that mentions cookies, and anything else gets cut at about 1,000 characters.
   - Close your tab when you're done.

Write the metadata into `review.json`'s `post` block: title, slug, status, URL, edit URL, how you read it, and today's date.

## 2. Cold read, before any checking

Read the post top to bottom once, the way Ivan would a year from now. Don't run a tool and don't take notes on style. Then write the `cold_read` block:

- **What I remember:** two or three sentences.
- **Where I got lost:** quote the spot where you had to reread or your attention dropped.
- **The question left:** the one thing you wanted to know and didn't find.

Then answer R1 to R8 from `references/rubric.md` from that read alone. This is the part Ivan cares about most, and it's where a tidy, generic tutorial loses its points.

## 3. Mechanical checks

```bash
python3 -I .claude/skills/blog-review/check.py <scratchpad>/review/<slug>.md --json <scratchpad>/review/<slug>.check.json
```

Gates are final:

- em and en dashes
- banned vocabulary
- the giveaway phrases from `blog-draft/references/voice.md`
- `leak-check.sh`
- placeholders
- links that don't resolve
- citations without a Sources entry

Signals are evidence for your judgment, not verdicts:

- hypotheticals
- faceless provenance
- contrast formulas
- rhythm
- template headings
- citation order

The metrics (first person per 100 words, time anchors, versions named) feed R and V.

Text read through Chrome has curly quotes from WordPress, so don't grade quote style from it. A dash in the rendered text is still a dash the reader sees, and it fails W1.

## 4. Fact and source audit

Build the claim list the way `references/rubric.md` describes. For each claim, open the source and find the line that supports it:

- **Docs:** `curl -sL` with a browser User-Agent (Cloudflare and some docs sites block the default one), strip the tags, and `rg` for the claim.
- **Code:** fetch the pinned file and give `file:line`. Read the line yourself, don't trust the citation.
- **This repo:** when the post describes code or releases from this blog, check them against the repo and `gh release view`.
- **Internal links:** they must point to posts that exist.

A citation that points to the wrong source counts as `unsupported`, even when the claim is true. Name the source that does support it. That's how a future reread gets lost.

For first-person claims, look for a research file: `rg -l <slug or topic> .claude/blog/research/`. If none records the claim, mark it `ask-ivan`.

Spot-check claims without a citation that a reader would act on: commands, flags, defaults, menu paths.

Run independent fetches in parallel. For a post with more than about 15 claims, you can hand the source fetching to one `general-purpose` agent. Give it the claim list and source URLs and ask for quotes and line numbers back. Judge the verdicts yourself.

## 5. Judge, then grade

Write `<scratchpad>/review/<slug>.review.json` in the shape at the bottom of `references/rubric.md`, with every judged item, the claim list and the cold read. Then run:

```bash
mkdir -p .claude/blog/reviews
python3 -I .claude/skills/blog-review/grade.py <scratchpad>/review/<slug>.review.json <scratchpad>/review/<slug>.check.json .claude/blog/reviews/<slug>-<YYYY-MM-DD>
```

The grade comes from `grade.py`, not from your impression. The weights:

| Category | Weight |
|---|---|
| Future-self recall | 30 |
| Facts and sources | 25 |
| Structure | 15 |
| Voice | 15 |
| Wording | 15 |

Pass scores 1, partial 0.5, fail 0. Letters: A 90, B 80, C 70, D 60.

Caps override the score:

- A leak sets it to 0.
- A placeholder caps it at 59.
- A contradicted claim caps it at 79.
- A dash or a dead link caps it at 89.

Verdicts:

- **Ready:** an A with nothing to ask Ivan.
- **Ready after fixes:** a B or C, or an A with `ask-ivan` items.
- **Not ready:** a D, an F, or a leak.

Before you report, run `check.py` on your own `review.json` fixes and look at the dash and banned-word lines. A rewrite you suggest must pass the same rules.

## 6. Report in chat

- The grade, the score, the verdict, and any caps that applied.
- Category scores in one line each.
- The top five fixes, each with the exact rewrite.
- Claims that are contradicted, overstated or miscited, with the source line.
- The `ask-ivan` questions, batched. If any decide a grade item, ask them with `AskUserQuestion` and re-grade with his answers.
- `file:///` links to the `.md` and `.json` reports, plus the full edit and post URLs.
- If the SEO title or meta looks off, say "run /blog-seo". Don't grade them.

Keep the report to what Ivan would act on. The full item list lives in the `.md` file.

## Re-reviews

When a review for the same slug already exists in `.claude/blog/reviews/`, read it first. Report what changed: which items flipped and the score delta. Grade from scratch; don't carry verdicts over.
