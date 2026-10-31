---
name: project-blog-idea-queue
description: Blog post ideas Ivan chose to keep for later sessions, drafts in progress, and where the blog-* skill outputs live
metadata:
  type: project
---

Blog skills live in `.claude/skills/blog-*`. Outputs go to `.claude/blog/{ideas,research,drafts,reviews}/` (gitignored). Every file passes `.claude/skills/blog-ideas/leak-check.sh` first. Client names live only in `~/.claude/blog-denylist.txt`, never in the repo.

**In progress (2026-10-31):** `drafts/chrome-devtools-overrides.md` and `drafts/ai-agents-1password-service-account.md` (research: `giving-ai-agents-their-own-1password-key.md`), both reviewed on 2026-10-31, not yet published.

**Queued:** an accessibility audit of this blog against WCAG 2.2 AA. First test found 31 images without alt text in 14 old posts, six Flash embeds, one table without header cells, and 50 posts that skip from the title to h3. Ten criteria still untested. The report page has to pass what it claims. Source: `wp-content/themes/ik2/blocks/accessibility-report/report.php`, live at https://www.ivankristianto.com/accessibility/.

Published from this queue: the webfont split post (2026-10-26), WebMCP tools (2026-10-28), PHP compatibility scan (2026-10-31).

**Why:** Ivan picked these from the ideas lists in `.claude/blog/ideas/`.

**How to apply:** at the start of a blog session, offer the in-progress drafts and the queue before running a fresh `/blog-ideas`. When a post goes live, delete its research, Markdown draft and reviews, keep its card source, and move it to "Published" here.
