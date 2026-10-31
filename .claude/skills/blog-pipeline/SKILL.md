---
name: blog-pipeline
description: Take one blog topic for ivankristianto.com from research to a WordPress draft in one run, chaining blog-research, blog-draft, blog-review (with its fixes applied), blog-feature-image, blog-seo and blog-wp-draft in that order. Stops only for the research questions, the punch line pick and a final yes before writing to WordPress. Never publishes. Use when Ivan types /blog-pipeline, asks for "the usual flow" or "the whole thing", or wants a topic taken all the way to a WordPress draft. For a single step, use that step's skill; for an old post, use blog-refurbish.
user-invocable: true
---

# Blog pipeline: topic to WordPress draft

Input is one idea: Ivan's own description of a topic, an idea from `.claude/blog/ideas/*.md`, or a slug whose research file or draft already exists. Output is a WordPress draft with its card, alt text, excerpt, tags and Yoast fields, plus one report. Publishing is Ivan's.

Each step is the named skill, run in full with its own rules, checks and `leak-check.sh`. This file only adds the order, the gates and the hand-offs. Read each skill's SKILL.md when you reach it.

## Order, and why

Text first, then the things that depend on the text. On 2026-10-31, SEO ran before the review. The review's fixes and Ivan's edits then changed the intro and headings after the keyphrase work, and it had to be redone.

| Step | Skill | Stops for Ivan |
|---|---|---|
| 0 | blog-wp-draft `preflight` | only if the app password is stale |
| 1 | blog-research | **gate 1:** its questions |
| 2 | blog-draft | no |
| 3 | blog-review, then apply its fixes | no |
| 4 | blog-feature-image, with options | **gate 2:** punch line, plus review `ask-ivan` items |
| 5 | blog-seo (steps 2 to 5, on the local draft) | no |
| 6 | blog-wp-draft dry run | **gate 3:** create the draft? |
| 7 | blog-wp-draft `--apply`, then report | no |

Start at the first step whose output is missing. If `.claude/blog/research/<slug>.md` exists, skip step 1. If `.claude/blog/drafts/<slug>.md` exists too, start at step 3. Say which steps you skipped.

## 0. Check the password first

```bash
op run --env-file=.env.op.local -- python3 -I .claude/skills/blog-wp-draft/scripts/wp_draft.py preflight
```

A stale password only showed up at the very end on 2026-10-31. If this fails, tell Ivan now: he refreshes `ik2 prod` in the Agents vault while you research. Don't wait on it, and don't use Chrome.

## 1. Research (blog-research)

Run it fully. Its step 4 batch of questions is **gate 1**; ask it with `AskUserQuestion` and wait. Don't ask "go to draft?" afterwards; go on.

## 2. Draft (blog-draft)

Run it fully. Don't stop to ask for edits; the review does that job here. Its "When the research looks wrong" corrections go into the final report.

## 3. Review and fix (blog-review)

Run blog-review on the local draft, then apply its fixes to the draft yourself:

- Apply every `fix` that rewords, cuts or reorders what the draft or research file already says, or corrects a claim from a checked source.
- Hold back every `ask` item for gate 2. Never write a fact only Ivan has.
- After editing, run blog-draft's step 4 checks again (dashes, banned words, giveaway phrases, placeholders, `leak-check.sh`, `wc -w`) and `check.py`. Don't re-grade.
- If the verdict was "Not ready" (D, F or a leak), stop and report instead of going on. A draft that bad needs Ivan, not a card.

## 4. Feature card (blog-feature-image)

Build the card and three more punch-line options, as its "Offer Ivan options" section says. Post the four PNG links in chat, then **gate 2** is one `AskUserQuestion` call:

- the punch line pick, with labels "A" to "D";
- the review's `ask-ivan` items, up to three more questions.

Apply his answers: render the chosen line as `feature.*`, and put his facts into the draft. Then re-run the step 3 checks once more.

## 5. SEO fields (blog-seo)

Run blog-seo on the local draft: the stand-in audit (step 2), the keyphrase (step 3), the fields (step 4), and `seo.txt` (step 5). There's no WordPress post yet, so it stops at the file.

blog-seo lists body-level suggestions, such as the keyphrase in the intro or in a heading. Don't apply them yet. They go to gate 3.

## 6. Dry run, then gate 3

```bash
op run --env-file=.env.op.local -- python3 -I .claude/skills/blog-wp-draft/scripts/wp_draft.py push .claude/blog/drafts/<slug>.md --report <scratchpad>/wp-dry.json
```

Show Ivan, in a few lines:

- the title, word count, review grade and what was fixed;
- the card's PNG link and the alt text;
- the keyphrase, SEO title and description, with character counts;
- the body suggestions from step 5, each as an exact before and after;
- what the dry run would do: create the post, the new tags, the upload.

**Gate 3** asks one question with three answers:

- create the draft with the suggestions (recommended when they're only wording);
- create it without them;
- stop here.

If he picks the suggestions, apply them, re-run the step 3 checks and the dry run, then go on.

## 7. Create the draft and report

Run the same `push` with `--apply`. Every value in `checks` must be `true`; blog-wp-draft says what to do when one isn't.

Report:

- the edit and preview links (full URLs, from the report file);
- what's in it: card, tags, excerpt, Yoast fields;
- what Ivan still does: open the edit link to see Yoast's scores, read it through, publish;
- anything left open: unanswered questions, claims marked unverified, steps skipped.

Don't publish, schedule or change the post's status.

## When Ivan says "just report back"

Some messages say to run without stopping ("just report back when it's done"). In that case:

- **Gate 1** still applies, unless his message already answers the research questions. A post without his reasons is the main thing blog-review marks down.
- **Gate 2:** take your option A and keep going. Put the other options' links and the `ask-ivan` items in the report, so he can swap.
- **Gate 3:** stop before writing to WordPress, unless his message asked for the WordPress draft. Report the dry run instead.
