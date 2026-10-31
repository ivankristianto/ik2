---
name: feedback-never-run-unscoped-write-tools
description: "Never run a formatter, --fix linter or other write-mode tool without first confirming it only touches the intended files"
metadata:
  type: feedback
---

Never run a write-mode tool (formatter, `--fix` linter, codemod) until you've confirmed it only touches the files you mean. Check its docs or run it in check/dry-run mode on a copy first. Never let it near gitignored local files.

**Why:** On 2026-10-28, `wp-scripts format tests/secret-scan.test.mjs` in ik2 ignored the file argument and reformatted 52 tracked files and 216 gitignored local files (`.claude/blog-evals/`, `samples/`, plugin JS). `wp-scripts lint-js --fix` also rewrote a `node:test` import to `vitest`. Ivan said: "Never do that again".

**How to apply:** `pnpm lint` now runs only `lint:js` and `lint:css` (the `:fix` scripts are separate), so it is safe; never run `lint:js:fix`, `lint:css:fix` or `wp-scripts format` without scoping them first. To format a single file, match the surrounding style by hand, or run plain `prettier --check <file>` first. Also, don't commit changes the user hasn't approved (I committed a CI concurrency change I'd planned to ask about first).
