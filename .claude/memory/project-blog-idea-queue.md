---
name: project-blog-idea-queue
description: Blog post ideas Ivan chose to keep for later sessions, drafts in progress, and where the blog-* skill outputs live
metadata:
  type: project
---

Blog skills live in `.claude/skills/blog-*`. Outputs go to `.claude/blog/{ideas,research,drafts,reviews}/` (gitignored). Every file passes `.claude/skills/blog-ideas/leak-check.sh` first. Client names live only in `~/.claude/blog-denylist.txt`, never in the repo.

**In progress (2026-10-31):** `drafts/chrome-devtools-overrides.md` and `drafts/ai-agents-1password-service-account.md` (research: `giving-ai-agents-their-own-1password-key.md`), both reviewed on 2026-10-31, not yet published.

**Queued:** an accessibility audit of this blog against WCAG 2.2 AA. First test found 31 images without alt text in 14 old posts, six Flash embeds, one table without header cells, and 50 posts that skip from the title to h3. Ten criteria still untested. The report page has to pass what it claims. Source: `wp-content/themes/ik2/blocks/accessibility-report/report.php`, live at https://www.ivankristianto.com/accessibility/.

**WordPress draft 2098 (2026-11-05):** free embeddings / related posts (slug `free-embeddings-in-mysql`, title says MariaDB, prod and local both MariaDB). Local files: `drafts/free-embeddings-in-mysql*`, research, review. Not yet published.

**Post 2105, scheduled 2026-11-07 21:00:** strict CSP behind Cloudflare (slug `strict-csp-wordpress-cloudflare`), written while the CSP is Report-Only. It covers the email fix through v0.29.2 and the router `eval` violation. When `ENFORCE` flips, add a dated "Updated" note. Local files: `drafts/strict-csp-wordpress-cloudflare*`, research, review. Later edits go over REST, since blog-wp-draft refuses scheduled posts.

**Post 2109, scheduled 2026-11-10 21:00:** Modern Web Guidance long-task test (slug `testing-modern-web-guidance`, card option C). Experiment lives in `research/testing-modern-web-guidance/` (protocol, harness, 34 agent transcripts, results). Public copy: https://github.com/ivankristianto/mwg-longtasks-eval (MIT, local clone at `~/Works/Personal/mwg-longtasks-eval`), live demo https://ivankristianto.github.io/mwg-longtasks-eval/demo/ (GitHub Pages from `main`); the post links both. blog-wp-draft refuses scheduled posts, so later edits go over REST with a `modified` guard.

**WordPress draft 2154 (2026-11-09):** WordPress performance in an FSE theme (slug `wordpress-performance-2026-fse-theme`). Rewritten for voice and H2/H3 structure on 2026-11-09; local copy `drafts/wordpress-performance-2026-fse-theme.md` (has IMAGE: markers, so push it over REST, not blog-wp-draft). PSI screenshots are media 2157/2158; the old full-window ones, 2152/2153, are orphaned in the library.

Published from this queue: the webfont split post (2026-10-26), WebMCP tools (2026-10-28), PHP compatibility scan (2026-10-31).

**Why:** Ivan picked these from the ideas lists in `.claude/blog/ideas/`.

**How to apply:** at the start of a blog session, offer the in-progress drafts and the queue before running a fresh `/blog-ideas`. When a post goes live, delete its research, Markdown draft and reviews, keep its card source, and move it to "Published" here.
