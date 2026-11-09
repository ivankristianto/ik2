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
- Ivan's own live setup stays private too: a token's expiry date, vault or item IDs, account names or emails, Keychain item contents. Write the generic version ("the token runs out after 90 days"). The date the story happened stays.
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

Run `python3 -I .claude/skills/blog-draft/recent-posts.py 3` and read his latest posts, then read `references/voice.md` before you write the first sentence. Contractions everywhere (I couldn't, it's, doesn't), real commands and output in small code blocks instead of tables, and a closing "If you ..." list with Sources. `references/voice.md` quotes Ivan's own posts and shows what earlier drafts of this skill got wrong. The short version: start in a moment (when, what he was doing, what broke), say why he is writing, give the answer the way a colleague would in a sentence, show what it cost him, make his choices out loud with the reason, and talk to one reader. Use only moments, costs and feelings that the research file records or Ivan tells you. Every first-person sentence is a claim that needs a line in the research file: what he ran, measured, saw or chose, and also his hedges ("I only measured it on my laptop", "I haven't graded them myself") and motives ("I'd rather not block code I didn't read"). Those three examples were invented in November 2026 drafts and each cost a review item. No line, no sentence. Never write a `[Ivan: ...]` placeholder, a question to Ivan or a notes block into the draft: the file is the post, nothing else.

Global rules, strictly: no em dashes (use commas, periods or parentheses), no hype or consultant words (see the vocabulary guardrails in `~/.claude/CLAUDE.md`), no "it's not X, it's Y" or "not just X, but Y", no sweeping openings, no "In conclusion", no list-padding, no repeated sentence rhythm. Straight quotes. Sentence case headings. Cut any sentence that could appear unchanged in a random LinkedIn post.

## Shape

Default length by the idea's size: short 600, medium 1,000, long 1,500 words. Do not stretch it.

A post has jobs to do. They are not headings, and the order can bend to the story:

- **Title**: plain and specific. Prefer the symptom or the question a reader would search.
- **Opening**: what he built, found or measured, then the stake, in one or two sentences. The short version comes right after, then the moment and the motive. See "The first paragraph" below; it is the paragraph that decides whether anyone reads the rest.
- **Setting**: the version of every tool whose behaviour the post depends on (runtime, database engine and version, browser, CLI, package or skill version), once, where it first matters. Say which environment when local and production differ ("MariaDB 11.8 locally"). This was the most missed review item: the 1Password CLI, the MySQL or MariaDB question, the skill build.
- **The story, in order**: what he saw, what he assumed, what was actually going on, what he chose and why. Headings name what happened in this story ("Why I couldn't just update"), never a reusable label.
- **The fix**: numbered steps for anything a reader will follow, with code blocks tagged by language. When the post says he ran a tool, the install and run commands (and the config that mattered) are in fences a reader can copy; a tool named only in prose fails review.
- **What he has not tested, and what's next**: said plainly where it comes up, or in one short paragraph near the end. No disclaimer block. Say each limit once, next to the result it limits. "Doesn't establish", "doesn't isolate", "doesn't prove", "isn't a benchmark" after every finding read as a nervous draft, not a careful one: post 2154 had six before its rewrite. When the research file lists next steps, put them in a section before the close, headed from this post (what's next for this thing, by name): the untested paragraph, then a plain list, one step per item with its reason and a citation where a source backs it. Only steps the research records, preferring the ones Ivan picked; never invent a plan for him. The section follows the heading budget below, so it needs about 200 words or goes in without a heading. Leave it out when there's nothing left to do.
- **The close**: stop when the content stops. A nudge to the reader ("If you run PHP...") or an invitation beats a summary or a punchline.

### The first paragraph

The reader arrived from search or a link and is deciding whether to stay. Leaving is most likely in the first 10 seconds on a page,[nng-dwell] people read about 20% of a page's words,[nng-read] and scanners see little more than the first two words of a line.[nng-2words] Both posts rewritten on November 9, 2026 opened on background ("My older WordPress performance posts are about classic themes...", "Revisiting my old WordPress security article in 2026 made me want to...") and Ivan found both first paragraphs weak.

- **Sentence one names the concrete thing and its result.** What he built, found, measured or broke, with the number if there is one. "My homepage scored 100 in PageSpeed Insights on mobile and desktop on November 9, 2026." The focus keyphrase goes in this paragraph.
- **The first two words carry information.** Not a date, "So", "Recently", "Revisiting", "Back in" or "My older posts". The moment still belongs in sentence one; put its date after the result.
- **Sentence two gives the stake or the catch.** Why a stranger should care, or what surprised him: "That makes the site easier to maintain, but shipping code in an image doesn't make it read-only while the container runs."
- **One or two sentences, under about 50 words.** Shorter paragraphs get read; longer ones get skipped.[poynter]
- **"The short version: ..." is paragraph two**, so a reader has the answer within three sentences.[refactoring]
- **Background and motive move to paragraph three**: his older posts, why he's writing, who ran the tests. They still matter to the story, just not before the reader knows what they're getting.

Before the revisions in Process step 3, answer these about paragraph one. Every answer must be yes:

1. Would a reader from search know what they'll get after reading only the title and this paragraph?
2. Is sentence one about the thing, not the history?
3. Are its first two words a noun, a name or a number?
4. Is the stake in sentence two?
5. Is it two sentences or fewer and under 50 words?

Sources for this section: [nng-dwell] https://www.nngroup.com/articles/how-long-do-users-stay-on-web-pages/, [nng-read] https://www.nngroup.com/articles/how-little-do-users-read/, [nng-2words] https://www.nngroup.com/articles/first-2-words-a-signal-for-scanning/, [poynter] https://www.poynter.org/news/case-shorter-paragraphs (Eyetrack III, 2004, news articles, so directional), [refactoring] https://refactoringenglish.com/chapters/write-blog-posts-developers-read/ (practitioner advice: answer "is this for me?" within the title and three sentences).

### Headings, paragraphs and citations

Headings work at two levels. They are for a reader skimming back, not a frame to fill, and never one per job above.

- **H2s are the story's threads**: four or five in a 1,500-word post, fewer in a shorter one, each 200 words or more. Each one opens with a lead sentence that says what the thread settles, before its first H3. No heading on the opening.
- **H3s break a thread** so no stretch of prose runs past about three paragraphs or 250 words without a heading. An H3 holds at least two short paragraphs or a paragraph and a code block (50 words or more); thinner, it merges into its neighbour.
- **The close** sits under the last H3, or under its own short H2 when the last thread is about something else.

On November 9, 2026 Ivan read a post with four H2s of 270 words each and no subheadings and found it hard to read; the same post split into H2s with H3s read better. Earlier pipeline drafts went the other way, eight H2s of 150 words each, and read like docs. Template labels fail review even when the section is good: "What I haven't tested", "The fix", "The problem", "Background", "Overview", "Summary". Name what happened instead ("Why the nonce survives the page cache"). A section ends on its last fact, never a quip, moral or slogan pair ("The secrets were in one vault. The access wasn't.").

Paragraphs run two to four sentences, under about 80 words, that follow from each other: join them with the reason ("so", "because", "but") instead of starting each one over. Split a longer one where the subject changes. A short list of titles or names reads better as one sentence. Use a list only for steps or parallel items, a table only for comparing things on the same axes. A series of four or more numbers (scores from repeated runs, timings) goes in a small `text` code block, one row per series, not in a sentence.

When an assistant or tool did part of the work, name it once with what it is ("Rei, my AI assistant"), then say "it" or put the result first. Don't open sentence after sentence with its name.

Write the concrete thing, not a noun for the idea. "The asset-locality idea", "a different delivery requirement", "the loading boundary" and "the useful question is" all failed on November 9, 2026. Say what the code does or what the browser downloads.

Link related posts on the blog inline. Cite every outside source (docs, specs, changelogs, source files, the repo) with a numbered citation pointing at the exact doc section or file, not with an inline link.

Numbered citations are required whenever the post uses an outside source. Write `[n]` right after the sentence it supports (`[9][10]` for several), number them in order of first use, and end the post with a `## Sources` heading and one `- [n] https://...` line per number. The theme (`wp-content/themes/ik2/inc/footnotes.php`) turns them into superscript links with back links and hover previews, but only when both sides match, so never cite a number the list doesn't have. Inline outside links or an unnumbered Sources list skip that feature.

A citation promises the source says the sentence. Overstated citations failed F2 in three of the four reviews from October 31 to November 5, 2026, so hold each `[n]` to this:

- Find the line in the source that supports the sentence before you cite it, and keep it in `<scratchpad>/<slug>-sources.md` (claim, URL, quoted line). Never in the draft.
- Scope words match the source's scope: "only", "all", "every", "always", "never", "since X", "at all", "the one place", and every count. The source names one function, the post names one. One example in the docs stays one example.
- Cite the page that holds the fact, not an index page that links to it.
- Pin repo and code links to a tag or commit (`/blob/v0.28.1/...`, `/blob/<sha>/...`), never `main` or `master`.
- An old source for current behaviour carries its year in the sentence ("a 2022 Cloudflare post says Zaraz leaves the header alone").
- When a site refuses scripts (WordPress Trac returns 403, so its links fail the link check), cite a mirror that resolves, such as the wp-trac list archive at lists.wordpress.org.

## Process

1. Read the research file and the global style rules. Check that its Story or Questions for Ivan section says why he is writing, when it happened, and the versions the Setting job needs. If any is missing and was never asked, ask him now in one batch with concrete options from the research (`AskUserQuestion` in Claude Code, one chat message in other agents) before writing a word, and add his answers to the research file's Story marked `(from Ivan)`. If he already left it unanswered, write the post without it. Draft from the thesis and outline. Use only claims marked `verified` or `sourced`. Put `unverified` ones in the "not verified" paragraph or leave them out.
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
3. Revise four times, as separate passes: once for voice (read it next to the quotes in `references/voice.md`; would Ivan say each sentence out loud to a colleague?), once for specificity (swap each general statement for a number, a name, a command from the research), once for claims (walk `<slug>-sources.md` against every `[n]`, and every "I" sentence against the research file; fix the scope word or cut the sentence), once to delete anything that sounds generated (the table and phrase list in `references/voice.md`).
4. Run the reviewer's own mechanical checks, so the draft can't pass here and fail there:
   ```bash
   python3 -I .claude/skills/blog-review/check.py <draft>
   rg -n 'github\.com/[^ )]+/(blob|tree)/(main|master|trunk)([/)#]|\s|$)' <draft>   # unpinned code links, must be empty
   ```
   Every `gate` line must say PASS (dashes, banned words, giveaway phrases, placeholders, `leak-check.sh`, links, citation pairing). Fix every raised `signal` too (the first paragraph, template headings, heading depth, citation order, contrasts, hypotheticals, faceless provenance, rhythm, long paragraphs, repeated hedges, numbers in a sentence) unless you can say in the report why it misfires on this post. Check `words` against the length band. Also confirm every code block was run and every number matches the research file.
5. Report in chat: title, word count, the category and excerpt, anything flagged for Ivan (claims he must confirm, image ideas with alt text), and the draft path as a `file:///` link. Flags live in the chat reply only, never in the draft file. Ask for edits. Publishing is Ivan's call. A WordPress draft is created only when he asks for it, with `blog-wp-draft`.

## Images

Suggest where a diagram or screenshot would help and what it should show, in a line each. Provide alt text for each. Never use captures from client environments. The featured image is made by `blog-feature-image`, only when Ivan asks for it.
