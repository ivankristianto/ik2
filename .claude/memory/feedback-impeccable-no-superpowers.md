---
name: feedback-impeccable-no-superpowers
description: When using the impeccable skill, do not mix it with superpowers skills
metadata:
  type: feedback
---

When the `impeccable` skill is active, do not invoke any `superpowers:*` skills alongside it.

**Why:** User explicitly instructed this — mixing them causes conflicts or undesired workflow behavior.

**How to apply:** If a task triggers `impeccable`, skip the usual superpowers workflow checks (brainstorming, writing-plans, etc.) and follow `impeccable` alone.
