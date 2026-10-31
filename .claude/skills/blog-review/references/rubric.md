# Rubric

Item IDs, weights and caps live in `grade.py`. This file says how to judge each item. Every verdict is `pass`, `partial` or `fail`, with one line of evidence that quotes the post (or says what's missing). Every non-pass gets one of two things:

- `fix`: the exact sentence or heading you'd write instead, not advice about it. A fix may only reword, cut or reorder what the post or its research file already says, or correct it from a source you checked.
- `ask`: a question for Ivan, when the fix needs a fact only he has (when it happened, why he wrote it, what he ran, what came back, what he chose, the outcome, his versions). Never write that fact for him, not even as an example sentence. An invented motive or moment in a suggested rewrite is the same fabrication F4 fails a post for.

Be strict. A post that is accurate and tidy but could have been written by anyone loses most of R and V, and that's the point: Ivan reads these posts later to remember what he did.

## R: Future-self recall (30)

Ivan's test: a year from now, he opens this post and remembers what he did and what he learned. Judge these from the text alone, as if you'd forgotten the project.

- **R1 When.** A date, or a time he can place ("On October 21, 2026", "that night", "after the v0.12.2 release"). The post date in WordPress doesn't count. Partial: only a vague "recently".
- **R2 Why.** What prompted it, or why he's writing it down ("This post is largely for my own use", "the PHP team posted an advisory"). Fail when the post starts on the reader's problem with no trace of his.
- **R3 Setting.** What he was working on and the environment that matters to the result: project (generic for client work), browser or PHP version, host, stack. Partial: setting without the versions that change the answer. Also partial when it publishes details of his live setup that a reader can't use: a token's expiry date, vault or item IDs, account names or emails, Keychain item contents. The fix is the generic version ("the token runs out after 90 days"). The date of the story itself stays.
- **R4 What he did.** Commands, steps or code he actually ran, with what came back. Instructions in the second person with no sign he ran them are partial at best. "The examples here were checked" with no who, where or output is a fail.
- **R5 Choices.** What he picked over what, and why, including what didn't work. Fail when the post only lists options.
- **R6 Outcome.** What worked, what it fixed, a number if there is one.
- **R7 Open ends.** What he hasn't tested or doesn't know yet, said in his voice inside the story. A passive disclaimer ("it hasn't had an end-to-end UI check for these notes") is partial.
- **R8 The 30-second test.** Read only the title and first paragraph (skip a dated "Updated ..." correction note at the top; that's his convention, so judge the first paragraph after it). Can you say what this post records about Ivan's work, not just what topic it covers? Pass, or fail.

## F: Facts and sources (25)

F1, F2 and F3 are computed from the claim list, and F6 and F7 from `check.py`. You judge F4 and F5.

**Claim list.** Pull every checkable claim: version numbers, dates, defaults, behaviour ("body overrides return 200"), UI labels and menu paths, API and function names, quotes, numbers, and "X causes Y" statements. Skip opinions and the reader-facing advice that follows from them. For each one:

1. Open the cited source (or the most authoritative one if nothing is cited) and find the sentence, table row or line of code that says it. For code, grep the pinned file and give `file:line`.
2. Give one verdict:
   - `supported`: the source says it.
   - `contradicted`: the source says otherwise. This caps the grade at C.
   - `overstated`: the source says less (a "can" becomes "always", one example becomes a rule, a limitation becomes advice it doesn't give).
   - `unsupported`: no source says it, or the cited source is about something else (a miscitation). Say which source does support it, if you found one.
   - `ask-ivan`: a first-person or experiment claim ("on a local test page...", "I spent 9 hours") that no research file records. Never call it a hallucination and never pass it; Ivan confirms it.
   - `not-checkable`: a prediction or an opinion phrased as fact. Suggest a hedge if it reads as fact.
3. `evidence`: the quote or `file:line`. `fix`: the corrected sentence.

Hallucinations show up as specifics that look right: a menu label one word off, a version that never shipped, a flag the tool doesn't have, a function name that's close to the real one, a doc that doesn't say what the citation claims. Check every one; a confident tone is not evidence.

- **F4 Provenance.** Pass when first-person claims are in the research file (`.claude/blog/research/<slug>*.md`) or Ivan confirmed them. Partial when some are `ask-ivan`. Fail when the post describes experiments with no person, place or result behind them, or a claim contradicts the research file.
- **F5 Source quality.** Primary sources (official docs, the spec, the project's source or changelog). Code links pinned to a commit or tag. Partial: secondary sources where a primary one exists, or a branch link that will drift.

## S: Structure (15)

- **S1** The answer, or what the post settles, is in the first three paragraphs.
- **S2** Order follows time (what he saw, assumed, found, chose) or the steps a reader takes. Fail when sections could be shuffled without loss.
- **S3** Headings name things from this story. "Why I couldn't just update" passes; "Change response headers with DevTools overrides" is a doc label, partial.
- **S4** Steps a reader will follow are numbered, one action each, with code in tagged fences. Pass when there are no steps to follow.
- **S5** No punchline, moral or recap closing a section.
- **S6** Ends with a nudge or "If you ..." list and a Sources list, not a summary.
- **S7** Length matches the idea (short 600, medium 1,000, long 1,500 words, from `blog-draft`). Partial when padding or missing context is visible.

## V: Voice (15)

Read `../blog-draft/references/voice.md` first and listen against his quotes there.

- **V1** Opens in a moment from his work. "Imagine..." or a reader's hypothetical problem fails. A dated "Updated ..." note at the top doesn't count as the opening.
- **V2** It's his experience. `first_person_per_100_words` under 0.5 is a strong sign of a fail; his posts run 0.8 to 2.5.
- **V3** Contractions, plain words, short asides. Fail on documentation register throughout.
- **V4** Talks to one reader and hands them something to do.
- **V5** Holistic score 1 to 5: next to his recent posts, would a regular reader believe Ivan wrote this? Put the score and the single sentence that sounds least like him in the evidence. Pass at 4 or 5, partial at 3.

## W: Wording (15)

W1 to W3 come from `check.py` and can't be overruled. W4 to W7 have script signals; you rule on them, because the patterns also catch Ivan's own lines ("these notes" is in one of his published posts).

- **W4** Canned contrasts, slogan lines, tidy punchlines.
- **W5** "Imagine", "Suppose", "Let's say" standing in for a case he actually had. Fine inside a real story ("say your host blocks X"); a fail when it replaces one.
- **W6** Passive or faceless provenance: "was checked", "these notes", "the demo behind these notes". Who checked, where, with what result? Ivan wrote it, so it says "I".
- **W7** Sentence shapes repeat (same length, same opener, same "X. Y. Z." triple).
- **W8** Filler: sentences that restate the previous one, announce what's next, or hedge with nothing behind it.

## review.json

```json
{
  "post": {"title": "", "slug": "", "status": "draft", "url": "", "edit_url": "", "read_via": "chrome-preview", "reviewed": "YYYY-MM-DD"},
  "cold_read": {
    "what_i_remember": "two or three sentences, as a reader who finished it once",
    "lost_at": ["where attention dropped or you had to reread, quoted"],
    "question_left": "the one thing you wanted to know and didn't find"
  },
  "from_ivan": {"date": "YYYY-MM-DD", "story": "answers Ivan gave during the review, kept so re-reviews and blog-draft can use them"},
  "items": [
    {"id": "R1", "verdict": "fail", "evidence": "no date or time anywhere in the post", "ask": "When did you run into this, and what were you working on?"},
    {"id": "S3", "verdict": "partial", "evidence": "\"Change response headers\" is a doc label", "fix": "Blocking geolocation without touching nginx"}
  ],
  "claims": [{"claim": "", "source": "[3] NetworkPersistenceManager.ts:1026", "verdict": "supported", "evidence": "", "fix": ""}]
}
```

Leave out W1, W2, W3, F1, F2, F3, F6 and F7 (computed) unless you want to attach a `fix` to a failing one.
