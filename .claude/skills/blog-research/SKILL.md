---
name: blog-research
description: Research one blog post idea Ivan has chosen. Re-reads the relevant sessions for firsthand facts, verifies claims against primary sources and by reproducing them, checks what the blog and the web already say, and saves a sanitized research file for the draft step. Use when the user picks an idea from /blog-ideas, asks to research a topic for the blog, or types /blog-research.
user-invocable: true
---

# Blog research: make the post true before it is written

Input is one idea: a title from `.claude/blog/ideas/*.md` (newest first) or a topic Ivan describes. If more than one idea is named, do them one at a time. Output is `.claude/blog/research/<slug>.md`. The slug is lowercase kebab-case with no `/`. Do not write the article.

## Confidentiality

Same rules as `blog-ideas`: sessions and client repos are sources to read, never to quote or link.

- Restate client findings as generic facts. If a claim came from client code, reproduce the behavior in a small throwaway example (in the scratchpad, never in a client repo) and cite that, plus the public source of the underlying library.
- No client names, private repos, ticket keys, hostnames, colleague names, screenshots or log lines in the research file.
- Run `bash .claude/skills/blog-ideas/leak-check.sh <file>` before reporting. Any hit is a rewrite.

## 1. Recover the firsthand story

Find the sessions behind the idea (the ideas file says which project). Read what happened in order: the question, the dead ends, the cause, the fix, what was never verified. Use `jq` with `rg` and `head` on the session file, never the whole file (see `blog-ideas/SKILL.md` for the commands). Write down:

- The sequence of events in plain words.
- Numbers, versions and error messages worth keeping, with identifying parts removed.
- What Ivan chose and why. A decision with a reason is the part readers value most.
- What was never tested. Say so in the file, and later in the post.

## 2. Verify

Each factual claim the post will make gets a row: claim, how it was verified, source.

- **Primary sources first**: official docs, the source code of the library at a stated version, changelogs, specs (MDN, WordPress developer docs, RFCs, the project's GitHub). Use WebSearch and WebFetch. Prefer reading code over reading blog posts about code. Link the exact file and line or the exact doc section, with the version.
- **Reproduce where it is cheap**: run the command, write the 10-line snippet, load the page. Record the command and what it printed. Use the local stack from CLAUDE.md for WordPress behavior.
- Mark every claim `verified`, `sourced` (read in a primary source, not run), or `unverified`. Unverified claims either get checked or flagged for Ivan. Do not let one slip into the outline unmarked.
- Check versions and dates. Anything about a vendor feature, a CVE or a browser flag may have changed since the session. Re-check it today.

## 3. See what already exists

- Search ivankristianto.com (`https://www.ivankristianto.com/wp-json/wp/v2/posts?search=<term>&_fields=link,title.rendered`) so the post links to related posts and does not repeat one.
- Search the web for the top few existing write-ups. Note what they miss or get wrong. The gap is the reason this post exists. If nothing is missing, say so and suggest a narrower angle or dropping the idea.

## 4. Ask Ivan what the transcripts cannot tell you

The draft step never asks Ivan anything in the post itself, so research collects his answers now. Before saving, ask him with `AskUserQuestion`, in one batch of up to four questions:

- Why he is writing it (for his own notes, for a reader with the same problem, something else).
- When it happened and what he was doing (a rough date and the situation, no client names).
- What it cost him or what he suspected first, if the session does not show it.
- Anything the outcome depends on: did it ship, did the numbers move, what he is allowed to say.

Offer concrete options drawn from the session so he can answer with a click, and let him type his own. Write his answers into the Story, marked `(from Ivan)`. If he skips one, record it as not answered; the draft then leaves that part out rather than guessing.

## 5. Save the research file

Sections, in this order:

1. **Thesis**: the one thing a reader should leave knowing, in two sentences.
2. **Audience**: who gets value, and what they already know.
3. **Story**: the firsthand sequence, sanitized.
4. **Claims table**: claim, status, how verified, source URL.
5. **Snippets**: runnable examples that are safe to publish, each tested, with the command and output.
6. **Related**: existing posts on the blog and the best outside articles, as full URLs, each with a line on how it differs.
7. **Suggested outline**: five to eight headings in sentence case, shortest path from problem to answer. Include a title and a 150-character excerpt candidate.
8. **Questions for Ivan**: each question asked in step 4, with his answer or "not answered". Only things the transcripts cannot answer (why he is writing, when, what it cost, what the client outcome allows him to say).
9. **Leave out**: details cut for confidentiality or because they do not carry the point, described without naming them.

Run `leak-check.sh`, fix hits, then report in chat: the thesis, how many claims are verified versus not, anything Ivan left unanswered, and the file path as a `file:///` link. Ask whether to go to `/blog-draft`.

## Writing rules

Ivan's global style rules apply: no em dashes, no hype words, no "it's not X, it's Y" constructions, concrete sentences, full clickable URLs.
