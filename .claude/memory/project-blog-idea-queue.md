---
name: project-blog-idea-queue
description: Blog post ideas Ivan chose to keep for later sessions, and where the blog-* skill outputs live
metadata:
  type: project
---

Blog workflow skills: `blog-ideas`, `blog-research`, `blog-draft` in `.claude/skills/`. Outputs go to `.claude/blog/{ideas,research,drafts}/` (gitignored). Every file passes `.claude/skills/blog-ideas/leak-check.sh` first. Client names live only in `~/.claude/blog-denylist.txt`, never in the repo.

**Active (2026-10-26):** the webfont-split performance post. Research is done: `.claude/blog/research/webfont-split-inp.md`, with the lab and an audit script in `webfont-split-inp/`. Thesis was reframed from "how many files" to "where the cuts fall" (open-font lab: Latin in 2 to 3 slices ran 1.5 to 2 times slower, Arabic with a Latin-only webfont first 2 to 2.5 times). Ivan confirmed the fix is live in production (no field INP data shared yet) and wants a tutorial with a short story up front. Draft: `.claude/blog/drafts/webfont-split-text-layout.md`. Never write the client's name in anything public.

**Queued, Ivan wants these next:**
1. **WebMCP on this blog.** Read-only tools (`search_articles`, `list_articles`, `get_article`), shipped in v0.14.0. Fully public, no client risk. Check the angle against the existing post https://www.ivankristianto.com/chrome-modern-web-guidance/ first.
2. **Accessibility audit of this blog against WCAG 2.2 AA.** First test found 31 images without alt text in 14 old posts, six Flash embeds, one table without header cells, and 50 posts that skip from the title to h3. Ten criteria still untested. The report page has to pass what it claims. Source: `wp-content/themes/ik2/blocks/accessibility-report/report.php`, live at https://www.ivankristianto.com/accessibility/.
3. **Static PHP 8.x compatibility scan before an upgrade.** PHPCompatibility sniffs over a whole codebase, results sorted into "breaks on the target version", "already broken on 8.0+", and "never loaded". Static analysis cannot see dynamic properties or runtime-dependent type errors, so the follow-up is booting the target PHP version and clicking through. Needs a public reproduction (this blog's code or a sample plugin), no client output.

**Why:** Ivan picked these from the 2026-10-26 ideas list and the earlier list. The rest of that list is in `.claude/blog/ideas/2026-10-26.md`.

**How to apply:** at the start of a blog session, offer these before running a fresh `/blog-ideas`. Keep client details out of anything written to the repo, including this file.
