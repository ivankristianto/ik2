---
name: blog-draft
description: Write a blog post for ivankristianto.com from a chosen idea and its research file, in Ivan's voice, readable by someone who has never seen the project, with no client details. Saves a Markdown draft. Does not publish. Use when the user asks to write, draft, or turn research into an article, or types /blog-draft.
user-invocable: true
---

# Blog draft: write it for a person, not for a keyword

Input is a research file in `.claude/blog/research/` (newest first unless Ivan names one). If there is no research file for the idea, run the `blog-research` steps first or ask. Output is `.claude/blog/drafts/<slug>.md`. Never publish, create a WordPress post, or commit.

## Confidentiality

Write from the research file. Do not reopen client sessions or repos for more detail. If the draft needs a fact that is not in the file, go back to research for it. Public sources are fine to re-check (see "When the research looks wrong").

- No client names, private repos, ticket keys, hostnames, colleague names or client-specific numbers. Name public products, open-source projects and standards freely.
- Describe the setting generically ("a multisite WordPress install on a managed host") and keep the finding.
- Screenshots and logs from client work are out. Offer a local reproduction or a described figure instead.
- `bash .claude/skills/blog-ideas/leak-check.sh <draft>` must exit 0 before you report.

## When the research looks wrong

A `verified` mark is the research step's word, not proof. Checking a claim against its cited public source (docs, the library's code, the RFC) is part of drafting, and the post follows the source when they disagree. Readers who know the subject will spot the error, and the post carries Ivan's name.

- If the source settles it, write the corrected version and tell Ivan what changed.
- If it does not, keep the story but say in the post what you saw and that the cause is not pinned down, and add it to the "not verified" paragraph.
- Doubt the explanation, never the observation. What Ivan saw ("the search returned a draft") stays in the post as he recorded it; only the cause he gave for it gets hedged or corrected.
- Keep a doubtful claim out of the title and excerpt. Never leave a claim in the post that your chat report tells Ivan you doubt.

## Who reads this

A developer who knows WordPress or the web basics and has not seen your project. They arrived with a problem, from a search or a link. Start where they are, give them the answer early, then show how you got there. Define a term once, in the sentence where it first matters. Assume nothing about the tool being discussed.

## Voice

Run `python3 -I .claude/skills/blog-draft/recent-posts.py 3` and read his latest posts, then read `references/voice.md` before you write the first sentence. Contractions everywhere (I couldn't, it's, doesn't), real commands and output in small code blocks instead of tables, and a closing "If you ..." list with Sources. `references/voice.md` quotes Ivan's own posts and shows what earlier drafts of this skill got wrong. The short version: start in a moment (when, what he was doing, what broke), say why he is writing, give the answer the way a colleague would in a sentence, show what it cost him, make his choices out loud with the reason, and talk to one reader. Use only moments, costs and feelings that the research file records or Ivan tells you. Never write a `[Ivan: ...]` placeholder, a question to Ivan or a notes block into the draft: the file is the post, nothing else.

Global rules, strictly: no em dashes (use commas, periods or parentheses), no hype or consultant words (see the vocabulary guardrails in `~/.claude/CLAUDE.md`), no "it's not X, it's Y" or "not just X, but Y", no sweeping openings, no "In conclusion", no list-padding, no repeated sentence rhythm. Straight quotes. Sentence case headings. Cut any sentence that could appear unchanged in a random LinkedIn post.

## Shape

Default length by the idea's size: short 600, medium 1,000, long 1,500 words. Do not stretch it.

A post has jobs to do. They are not headings, and the order can bend to the story:

- **Title**: plain and specific. Prefer the symptom or the question a reader would search.
- **Opening**: the moment it happened and what the post settles, in a few sentences. The short answer belongs here or right after, in a sentence or two ("The short version: ..."), so a reader who only needs the fix gets it in ten seconds.
- **The story, in order**: what he saw, what he assumed, what was actually going on, what he chose and why. Headings name what happened in this story ("Why I couldn't just update"), never a reusable label.
- **The fix**: numbered steps for anything a reader will follow, with code blocks tagged by language. Give versions where they matter, once, not as a ritual on every snippet.
- **What he has not tested**: said plainly where it comes up, or in one short paragraph near the end. No disclaimer block.
- **The close**: stop when the content stops. A nudge to the reader ("If you run PHP...") or an invitation beats a summary or a punchline.

Paragraphs run one to four sentences. Use a list only for steps or parallel items, a table only for comparing things on the same axes. Link public sources with full `https://` URLs on first mention, to the exact doc section or source file; several sources can also go in a plain list at the end. Link related posts on the blog.

## Process

1. Read the research file and the global style rules. Check that its Story or Questions for Ivan section says why he is writing and when it happened. If either is missing and was never asked, ask him now in one batch with concrete options from the research (`AskUserQuestion` in Claude Code, one chat message in other agents) before writing a word, and add his answers to the research file's Story marked `(from Ivan)`. If he already left it unanswered, write the post without it. Draft from the thesis and outline. Use only claims marked `verified` or `sourced`. Put `unverified` ones in the "not verified" paragraph or leave them out.
2. Frontmatter at the top of the file:
   ```yaml
   ---
   title:
   slug:
   excerpt: <under 160 characters>
   category: <slug from https://www.ivankristianto.com/wp-json/wp/v2/categories>
   tags: [lowercase, lowercase]
   status: draft
   ---
   ```
3. Revise three times, as separate passes: once for voice (read it next to the quotes in `references/voice.md`; would Ivan say each sentence out loud to a colleague?), once for specificity (swap each general statement for a number, a name, a command from the research), once to delete anything that sounds generated (the table and phrase list in `references/voice.md`).
4. Checks before reporting:
   ```bash
   rg -n '\x{2014}|\x{2013}' <draft>            # em and en dashes, must be empty
   rg -n -i 'delve|seamless|robust|leverage|crucial|pivotal|comprehensive|powerful|showcase|landscape|unlock|elevate' <draft>
   rg -n -i -F -f <(sed -n 's/^- `\(.*\)`$/\1/p' .claude/skills/blog-draft/references/voice.md) <draft>   # giveaway phrases, must be empty
   rg -n -i '\[ivan|notes for ivan|\bTODO\b' <draft>   # placeholders or notes to Ivan, must be empty
   bash .claude/skills/blog-ideas/leak-check.sh <draft>
   wc -w <draft>
   ```
   Also confirm every code block was run, every URL resolves (`curl -sI`), and every number matches the research file.
5. Report in chat: title, word count, the category and excerpt, anything flagged for Ivan (claims he must confirm, image ideas with alt text), and the draft path as a `file:///` link. Flags live in the chat reply only, never in the draft file. Ask for edits. Publishing is Ivan's call, and a WordPress draft is created only when he asks for it.

## Images

Suggest where a diagram or screenshot would help and what it should show, in a line each. Provide alt text for each. Never use captures from client environments. The featured image is made by `blog-feature-image`, only when Ivan asks for it.
