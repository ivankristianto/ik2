---
name: project-old-post-refresh
description: How old posts get refreshed on prod - dated "Updated" callout on top, new feature card, Yoast fields - and the browser tricks that make it work
metadata:
  type: project
---

Refreshed so far: 655 (obfuscator, 2026-10-28), and on 2026-10-30 626 SeoQuake, 897 OpenSolaris, 845 VPN, 949 text editor, 572 internet cables (noindex removed on Ivan's request); 2026-10-31 621 PHP/MySQL/Flash (media 2061, old 1032 kept), the first run of `/blog-refurbish`. Files per post in `.claude/blog/drafts/<slug>/` (update.md, callout.html, feature.*, alt.txt, seo.txt); research in `.claude/blog/research/<slug>.md`.

**Why:** Ivan keeps the old text and adds a first-person "Updated <date>" note on what he'd do today. GSC/GA4 picks which posts (impressions with 0 clicks, or real engaged visitors).

**How to apply:** run `/blog-refurbish` (`.claude/skills/blog-refurbish/`, added 2026-10-31); it carries the steps and browser code. Gotchas kept here for context:
- Callout is a `core/group` with `className: is-style-callout-updated`, first paragraph `ik-callout__title`. Prepend it to `content.raw` over `wp.apiFetch` from a logged-in wp-admin tab, then check the saved raw equals callout + old raw.
- Ask Ivan's current stance per post before drafting (one AskUserQuestion batch); the note is his opinion.
- The Chrome tool refuses to return text containing query-string URLs, so raw content can't be read back out; the byte-for-byte suffix check is the backup.
- Cards are uploaded with `wp_draft.py feature` over REST now, not through the browser (see [[reference-yoast-og-image-after-rest-featured-media]]).
- Yoast counts the post date in the meta description, so keep descriptions under about 135 chars on dated posts. `introductionKeyword` stays amber on every refreshed post; the likely cause (unconfirmed) is Yoast reading the callout title paragraph as the intro.
- A post can have zero GSC queries (621: 1 impression in 16 months, query anonymised). Then the keyphrase comes from the title's specific pairing, and the review says it has no recorded demand.
- The Chrome tool blocks returning `content.raw` even without query-string URLs in it ("Cookie/query string data"), so expect the scratchpad copy of the backup to fail every time.
- Per-post noindex is the `yoast_wpseo_meta-robots-noindex` field; `setNoIndex('0')` on `yoast-seo/editor` restores the default. See [[project-prod-page-content-via-rest]], [[project-blog-idea-queue]].
