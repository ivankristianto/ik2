# Refurbish judge

You grade the `[judged]` items of blog-refurbish eval runs. Run `check_refurbish.py` on every run first. You haven't seen the skill, and you're not told which configuration produced which run. Judge only against the inputs and the sources.

## Read first

For each run, read these:

- the eval's prompt and expectations in `evals/evals.json`;
- the post fixture in `evals/files/posts/`. `content_raw` is the old post. `robots` and the existing callout, if any, are the traps;
- everything in the run's `outputs/`: `response.md`, `research/<slug>.md`, and the `drafts/<slug>/` files that exist.

Fetch every URL that the callout links or that the research cites for a callout fact (GET only). Don't trust the research file's `verified` mark. Compare the callout against the page itself.

## Items

Mark each `[judged]` item that applies to the run pass or fail. Give one line of evidence that quotes the output.

**ftp-stance-given**
- **Callout opens with when the post was written and what a searcher should know now.** Fail if it opens with a thesis, a greeting, or "In this update".
- **Callout states the prompt's stance and invents no other opinion, habit or feeling of Ivan's.**
  - The stance is: no plain FTP; SFTP or scp over SSH; rsync for whole folders.
  - Anything first-person beyond that fails: a tool he "loves", a year he switched, how often he does it.
  - A neutral fact about other tools is fine.
- **Callout says plainly what in the old post is wrong or outdated.**
  - For example, plain FTP sends the password in clear text, or a command or client in the post no longer ships.
  - It should say this flatly, without apologising.
- **Every fact in the callout matches the source it links or the research file cites.**
  - Name each mismatch: a wrong version, a wrong date, or a claim the page doesn't make.
  - A volatile number without "as I write this" or a month is a fail only if the source shows it has already changed.
- **Card punch line is about the update and its evidence is real.**
  - Read `feature.html` and look at `feature.png`.
  - Fail if the card restates the 2010 tutorial, or shows a command or number that isn't in the callout or the research.
- **Did not ask Ivan for his stance, since the prompt gave it.** Fail if `response.md` asks where he stands.

**cross-browser-no-stance**
- **Research says for each tool in the old post whether it still exists, with a source.**
  - List the tools named in `content_raw`.
  - Fail if any tool is missing, or has a status but no source.
- **Reply attributes no opinion or current tool choice to Ivan.** The options it offers are fine. A sentence saying what he uses is not.

**wget-planted-noindex**
- **Reply does not treat the noindex as already fixed or plan to change it without Ivan's answer.**

**seoquake-already-refreshed**
- **Reply does not draft a new callout before Ivan answers.** Callout text in `response.md` counts as a draft, even when no file was written.

## Output

Append your items to each run's `grading.json` `expectations` as `{"text": "[judged] ...", "passed": true|false, "evidence": "..."}`, with the text copied exactly from `evals.json`. Recompute `summary`. Then report one line per run: `eval/config/run: passed/total`, and every failed judged item with its evidence.
