---
name: blog-wp-draft
description: Create or update the WordPress draft for a finished ivankristianto.com blog draft over REST, signed in through 1Password. Converts the Markdown to the site's block markup, finds or creates tags, uploads the feature card with its alt text, sets the excerpt, category and Yoast fields from seo.txt, then reads everything back to check it. Never publishes and never touches a post that isn't a draft. Use when Ivan asks to create, push, upload or update the WordPress draft, "put it in WordPress", or types /blog-wp-draft. Runs after blog-draft, blog-review, blog-feature-image and blog-seo.
user-invocable: true
---

# Blog WP draft: local draft to WordPress draft, over REST

Input is a local draft, `.claude/blog/drafts/<slug>.md`. The script picks up these files from `.claude/blog/drafts/<slug>/` when they're there:

- `feature.webp` or `feature.png`, whichever is smaller;
- `alt.txt`, which is required when there's a card;
- `seo.txt`, with lines `Focus keyphrase: ...`, `SEO title (n): ...` and `Meta description (n): ...` (blog-refurbish's `keyphrase:`, `title:` and `description:` work too).

The output is a WordPress draft on https://www.ivankristianto.com/ with all of those set, plus a JSON report. Publishing stays Ivan's job.

Use REST, not Chrome. Ivan stopped a Chrome attempt on 2026-10-31 and refreshed the app password instead.

## Credentials

`op run --env-file=.env.op.local` supplies `IK2_PROD_USER` and `IK2_PROD_APP_PASSWORD` from `op://Agents/ik2 prod/...` to the script's process only. Never read, print or write those values.

`op run` masks the username in stdout. The username is part of `ivankristianto.com` and of the scratchpad path, so links come out mangled. The script therefore prints one status line and writes everything else to `--report`. Read that file with `cat` afterwards, outside `op run`.

## Steps

The scripts live in `.claude/skills/blog-wp-draft/scripts/`. Run them from the repo root.

1. **Check the password.**
   ```bash
   op run --env-file=.env.op.local -- python3 -I .claude/skills/blog-wp-draft/scripts/wp_draft.py preflight
   ```
   A 401 means the app password in 1Password is stale. Stop and ask Ivan to refresh `ik2 prod` in the Agents vault. Don't fall back to Chrome or to another vault.
2. **Convert and dry-run.**
   ```bash
   op run --env-file=.env.op.local -- python3 -I .claude/skills/blog-wp-draft/scripts/wp_draft.py push .claude/blog/drafts/<slug>.md --report <scratchpad>/wp-dry.json
   cat <scratchpad>/wp-dry.json
   ```
   A dry run reads WordPress and writes nothing. It reports:
   - the post it found by slug, or that it would create one;
   - the tags it would create;
   - whether it would upload the card or reuse the current one, which it does when the size and type match;
   - `content_diff` when the post already exists.

   If the converter stops on Markdown it can't map, it prints the line. This covers bold, images, tables, nested lists, raw HTML, H1 in the body, and a fence language missing from `wp-content/plugins/ik2/src/code-highlight/languages.json`. Fix the draft. Don't add a workaround in the script. `text` fences get no language; `sh`, `zsh` and `shell` become `bash`.
3. **Show Ivan the dry run, then apply.**
   - **Create:** skip the question when his message already asks for the draft ("create the wordpress draft").
   - **Update:** when the report has a `content_diff`, always show it and ask, whatever his message said. `push` replaces the WordPress content with the local file, so edits he made in the editor would be lost. Also tell him to close any open editor tab for that post first, because saving a stale tab writes the old content back.

   Then run the same command with `--apply` and `--report <scratchpad>/wp-apply.json`.
   - On create, the post is always `status: draft` and the card is attached to it.
   - On update, only the changed fields are sent, and the status is never changed.
   - A post that exists and isn't a draft makes the script refuse with exit 2. Tell Ivan and stop.
4. **Read the checks.** Every value in `checks` must be `true`:
   - the content round-trips byte for byte;
   - title, excerpt, category, tags, featured image and alt text;
   - each Yoast meta key;
   - Yoast's rendered `yoast_head_json` title and description.

   Exit 1 means at least one is `false`. Report which ones. Don't rerun blindly; read the post first.

## Yoast fields on their own

blog-seo uses the same script to set the three Yoast fields on any post, draft or published:

```bash
op run --env-file=.env.op.local -- python3 -I .claude/skills/blog-wp-draft/scripts/wp_draft.py seo <post-id> <seo.txt> --report <scratchpad>/seo-apply.json [--apply]
```

It changes nothing else, and it checks the status didn't move. On a published post (`"live": true` in the report) the new fields go live on apply, so say so before running it.

## Report

- Post ID, status (draft), and the full edit and preview links from the report file.
- What changed: tags created, media uploaded or reused (with ID), fields set.
- Any warning, for example no `seo.txt` (Yoast left as is) or no card.
- Yoast's SEO and readability scores only exist after the editor runs its analysis. Ask Ivan to open the edit link and glance at them. If he wants it done for him, blog-seo step 5 reads them in Chrome.

## Tests

```bash
python3 -I -m unittest discover -s .claude/skills/blog-wp-draft/tests
```

Change `md2blocks.py` only with these passing. When a published post shows the converter got some markup wrong, add the case to `tests/fixtures/sample.md` and regenerate `sample.blocks.html` after checking the diff by eye.

To try the write path without touching production, point the script at the local stack. Set `IK2_WP_SITE=http://localhost:8080`, plus a throwaway local application password in `IK2_PROD_APP_PASSWORD`. Local application passwords need a temporary mu-plugin that returns true from `wp_is_application_passwords_available`, because the stack is plain http. Delete the test post, media, tags, app password and mu-plugin afterwards.
