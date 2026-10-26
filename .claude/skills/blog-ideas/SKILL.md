---
name: blog-ideas
description: Mine Ivan's recent Claude Code sessions across every project for blog post candidates that fit his interests, with all client and employer details removed. Use when the user asks for blog ideas, "what should I write about", article topics from recent work, or types /blog-ideas. First step of the blog-ideas, blog-research, blog-draft chain.
user-invocable: true
---

# Blog ideas: find what is worth writing, publicly

Read recent sessions, pick the work that makes a good post for ivankristianto.com, and report it with every client detail stripped. Output is a ranked shortlist. Do not research or draft here.

## Confidentiality comes first

Most sessions are client work (anything under a folder listed in `~/.claude/blog-leak-projects.txt`). The raw session text names clients, people, tickets and private repos. Read it, never repeat it.

- Describe context generically: "a WordPress VIP site", "a multisite install", "a financial reporting microsite", "an internal set of coding guides for agents".
- Public products and open-source pieces can be named (WordPress, VIP, Two Factor, a SAML plugin, Docker, PHPStan). Client names, client sites, private repos, ticket keys, internal hostnames and colleague names cannot.
- No links or file paths into client repos or tickets, not even in the chat reply.
- Keep the findings that make the idea good (the cause, the number, the surprise) and drop the identity. If an idea only works with the client named, drop the idea.
- Every file this skill writes passes `.claude/skills/blog-ideas/leak-check.sh` before you report. It exits 1 on any hit. Fix the hits and rerun. A hit is a rewrite, not a judgment call.

## 1. Gather

1. Sessions from the last 14 days (ask only if Ivan names another window):
   ```bash
   bash .claude/skills/blog-ideas/sessions.sh 14 5 > <scratchpad>/sessions.txt
   ```
   It prints each session's date, project folder and first prompts. Session files run to hundreds of MB, so never `cat` or `Read` them whole. Go deeper only with `jq` plus `head` on one file.
2. For sessions that look promising, read what the assistant concluded, not only what Ivan asked:
   ```bash
   jq -r 'select(.type=="assistant") | .message.content[]? | select(.type=="text") | .text' <file> | rg -i -B1 -A6 '<keyword>' | head -80
   ```
   Many sessions are wide. Split the reading across Explore or general-purpose agents, one per project folder, and brief each with the confidentiality rules above and an instruction to return anonymized notes only.
3. This repo's own work: `git log --since="14 days ago" --format='%h %ad %s' --date=short` and `.claude/memory/MEMORY.md`.
4. What is already published, so nothing repeats:
   ```bash
   curl -s 'https://www.ivankristianto.com/wp-json/wp/v2/posts?per_page=100&orderby=date&_fields=link,title.rendered,categories'
   curl -s 'https://www.ivankristianto.com/wp-json/wp/v2/categories?per_page=50&_fields=id,name,slug,count'
   ```
   Search the site (`?search=<term>`) for each shortlisted topic.

## 2. Pick

Ivan writes as a working engineer. Categories on the blog are WordPress, Tooling, Performance, Web APIs, AI, Guides, Talk and Misc. Favor:

- Something he found out firsthand: a root cause, a measurement, a failed attempt, a decision with a reason.
- WordPress internals and operations, performance, accessibility, web platform features, developer tooling, AI agents used for real work, self-hosting and deploys, and small personal automation.
- Reusable beyond one project. A reader with a similar stack should be able to use it.

Skip: routine tickets, anything that needs the client to be recognizable, anything already covered on the blog (unless there is a new angle, then say so), and ideas where the only evidence is a one-line prompt and nothing came out of it.

## 3. Report

Write the shortlist (5 to 10) to `.claude/blog/ideas/YYYY-MM-DD.md`. Create the folder if needed. It is gitignored, so it never trips the release script. Each idea:

- **Working title** and a one-sentence hook, in plain words.
- **Why it is worth writing**: the firsthand fact that carries it.
- **Evidence**: `verified in session` (the cause or result was read in the transcript) or `inferred from prompt` (only the question was seen, Ivan needs to supply the story).
- **Category** from the live list, and size: short (about 600 words), medium (about 1,000), long (1,500 or more).
- **Gaps**: what Ivan has to fill in, or what must be checked before publishing (for example "never tested live").
- **Overlap**: any existing post that touches it, as a full URL.

Rank by how strong the firsthand evidence is, then by reuse. Put a one-line recommendation first. Then run `leak-check.sh` on the file, fix hits, and show Ivan the list in chat in the same shape, shorter.

End by asking which idea to research next. Do not start `/blog-research` unprompted.

## Writing rules

Ivan's global style rules apply to everything written here: no em dashes, no hype words, no "it's not X, it's Y" constructions, short concrete sentences, full clickable URLs for public pages.
