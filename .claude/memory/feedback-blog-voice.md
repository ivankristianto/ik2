---
name: feedback-blog-voice
description: Blog drafts must sound like Ivan's real posts and must never contain [Ivan: ...] placeholders; ask him during research instead
metadata:
  type: feedback
---

On 2026-10-26 Ivan rejected the first webfont draft: "feels like not me, and not very good for user read". The draft had an invented structure, tables, test-method detail, a 35-line script mid-article and few contractions.

Later the same day he rejected `[Ivan: ...]` gaps in the draft: "i don't like you ask me in the draft like that, anything should be done during the research if you need to ask me anything or ask user question".

**Why:** his real posts (PHP CVE post, MCP talk recap, Modern Web Guidance notes) open on a dated trigger, give "the short version" in one line, tell the story in time order with plain headings, use contractions everywhere, show real commands and output in code blocks, list limits flatly, and end with "If you ..." bullets and Sources. He reads the draft file as the post, so questions or a notes block inside it read as unfinished work.

**How to apply:** `/blog-research` asks him with AskUserQuestion (why he's writing, when, what it cost or what he suspected, the outcome) and records answers in the Story as `(from Ivan)`. `/blog-draft` asks only if those are still missing, never writes placeholders or a "Notes for Ivan" block, and puts flags in the chat reply. Never invent his feelings or time spent. Results go in small code blocks, not tables. Voice guide: `.claude/skills/blog-draft/references/voice.md`. See [[project-blog-idea-queue]].

**Heading depth (2026-11-09, post 2154):** after the "fewer headings" rule, Ivan found the paragraphs between H2s too long: "Break it down between h2 and h3". Keep 4 to 5 story-named H2s, give each a one-line lead, then H3s so no run goes past about three paragraphs. blog-review's S7m heading-density signal will flag this; his ask wins.

**Screenshots (same day):** full-window captures render too small in the 960px column. Crop to the part that matters (for PSI: device tabs, score gauges and metrics, about 1100px wide) and put the date and report link in the figcaption.
