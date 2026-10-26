# How Ivan writes

Built from his posts on https://www.ivankristianto.com/ (2010 to 2026). Read this before the first sentence of a draft, and again before the last pass. The examples are his words, quoted so you can hear them, not so you can reuse them.

## What his posts do

**They start in a moment, not a thesis.** A day, a time, what he was doing, what went wrong.

- "Yesterday night I was fixing somebody else's code and auditing the performance as well as the security." ([headers already sent](https://www.ivankristianto.com/fix-strange-headers-already-sent-line-1-php-file/))
- "This weekend I spent hours learning prediction.io." ([machine learning](https://www.ivankristianto.com/learning-to-build-machine-learning/))
- "On October 21, 2026 the PHP team posted a security advisory called Critical remote code execution vulnerabilities, update now. That's not a title you see on php.net very often." ([PHP bugs](https://www.ivankristianto.com/php-cve-2026-109618-109619/))

**They say why he is writing, plainly.** Often it is for himself first.

- "This post is largely for my own use. In a few months I may forget how this process functions." ([Chrome guidance](https://www.ivankristianto.com/chrome-modern-web-guidance/))
- "I wrote this for my own records, but if it helps you and you have something in mind, just let me know."

**They give the answer the way a colleague would, in a sentence, not a section.**

- "The short version: two bugs, and both let an attacker run code on your server without logging in. [...] If you can update, go do that first and come back later."
- "It can, with one catch. Here's the short English version." ([MCP talk](https://www.ivankristianto.com/mcp-talk-kelas-tanya-domainesia/))

**They show what it cost him, in his units.** Hours, nights, a release number, a lost day.

- "It confused me for an hour until I finally got the answer from this StackOverflow thread."
- "Total hours spent: 9 hours, basically one full working day. All of this because I didn't back up that server." ([backup](https://www.ivankristianto.com/lesson-learned-why-backup-is-important/))
- "About 13 hours from advisory to patched PHP in production."

**They make choices out loud, with the reason attached.**

- "I wasn't going to remove a guard that had just done its job, for a bug nobody has exploited on FPM yet."
- "I couldn't update that night. Here's what the bugs are and what I did instead."

**They are allowed to be short and a little dry.** One-word sentences and small asides are his, used rarely.

- "Fatal error backtraces off. My error logs get a little less detail for one night. Fine."
- "Install Git: Thanks Linus."

**They admit what he does not know yet, inside the story, not in a disclaimer block.**

- "The training part is still unclear to me. I need more time to learn how it works."
- "PHP-FPM, which is what I use, is probably affected too, but nobody has shown a working exploit on it yet."

**They talk to one reader, and end by handing it over.** Second person shows up when he gives advice, and the close is often a nudge or an invitation, not a summary.

- "So if you encounter any strange errors and come to this article, it is worth checking it out."
- "Have to wait? Put the advisory's settings in php.ini or the FPM pool, restart PHP, and leave yourself a note to remove them once you've updated. That note is the only reason I remembered to revert mine the next morning."

**Headings name what happened in this story.** "Why I couldn't just update", "The stopgap", "The next morning: the real fix", "Challenge #2: We don't have enough speakers". You could not paste them into a different post.

**Sources go in a plain list at the end** when there are several, and corrections go at the top with a date ("Updated October 25, 2026").

## What made earlier drafts read as generated

These came from test drafts of this skill. Each one is a pattern, so a near copy is just as bad.

| Generated | Why it fails | Closer to Ivan |
|---|---|---|
| "Here is how that happened, how I closed it, and two smaller problems I hit with errors and cancellation." | A table of contents in sentence form. Nobody talks like that. | "My first version handed the agent my drafts." Then tell it. |
| A `## The short answer` heading, then `## What I have not verified`, then `## Where this stops applying`, in every post. | The skill's outline pasted as headings. Every post gets the same skeleton. | Put the short answer in the opening paragraph. Fold the unknowns into the section where they come up. Name headings after the story. |
| "The fix has two parts:" / "Two things to watch:" / "The same endpoint has one more catch." | Counting announcements. They set up a list instead of saying the thing. | Say the first thing. Then the second. |
| "Odds are it names the wrong domain." / "That's the extent of it." / "Readers who only need the fix can stop here." | A tidy punchline closing a section. Ivan's sections stop when the content stops. | Cut it. |
| "I should be honest about one thing." | Announces honesty instead of being honest. | Just say it: "I still don't know why WordPress answered as me." |
| Bold lead-in bullets (`- **Subdirectory install.** ...`) for every list. | Reads like documentation. | Plain bullets, or a short paragraph each. |
| Every snippet introduced with "It runs in X, tested on Y version Z." | Precision as a ritual. | Give versions once, where they matter, the way he does: "I run PHP 8.5 on FPM, so this is the one I cared about most." |
| No "you" anywhere, no reason he cared, no time or place. | Information without a person. | Use the time, the cost and the worry that the research file records, and give the reader one line. |

## Phrases that give a draft away

The check script fails a draft that contains any of these (case-insensitive). Do not dodge the list with a synonym; rewrite the sentence.

- `here is how that happened`
- `here's how that happened`
- `has two parts`
- `two things to watch`
- `one more catch`
- `odds are`
- `that's the extent of it`
- `i should be honest`
- `to be honest about`
- `can stop here`
- `let's walk through`
- `in this post, i`
- `this post covers`
- `this post walks`
- `the takeaway`
- `key takeaway`
- `the bottom line`
- `at the end of the day`
- `it's worth noting`
- `it is worth noting`
- `the good news`
- `the catch is`
- `here's the thing`
- `turns out`
- `the short answer`
- `where this stops applying`

## Do not invent the person

Every moment, hour, feeling and reason in the draft must come from the research file (its Story and Questions for Ivan sections) or from Ivan. If the story has no time, place or stake in it, leave a gap like `[Ivan: when did you notice this?]` in the draft and list it in your chat report. A made-up "I spent the evening on this" is worse than a flat sentence, because it is a false statement under his name.

Two traps from test drafts:

- **Why he is writing.** Research files rarely record it, and a post without it reads like documentation. If the file does not say, open with a gap (`[Ivan: why did you build this, or who was it for?]`) instead of skipping the motive or inventing one.
- **Your own checks are not his story.** Reading core, re-running a snippet or curling a URL while drafting is your work, not his. Never write it as "Reading core while writing this..." or "I ran this on Node 24" unless the research says Ivan did it. Put what you checked in the chat report, and let the post state the fact with its source link.

## Things not to copy from the old posts

Old posts have em dashes, exclamation marks, "voila", "like a charm" and the occasional emoticon. The global style rules win: no em dashes, exclamation marks only when something earned one. Keep his directness and his small asides, not the 2010 habits. (Quotes above have their em dashes swapped for commas.)
