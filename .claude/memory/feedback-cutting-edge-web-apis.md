---
name: feedback-cutting-edge-web-apis
description: On ivankristianto.com, use new web platform features natively (interestfor, popover=hint, anchor positioning...); never pitch the "play safe" option or polyfills
metadata:
  type: feedback
---

Ivan is a GDE (Web) and treats this site as the place to use the newest web platform features. Ship Limited-availability and Newly-available features natively as progressive enhancement, with no polyfills, and don't recommend holding back for Safari or Firefox.

**Why:** 2026-10-28, after I recommended skipping `interestfor` source previews because only Chrome/Edge support it: "i don't want to play safe, i am a GDE Web, i want advancement. so don't suggest me to play safe."

He weighs performance, accessibility and Core Web Vitals just as hard: "never suggest me to use JS libraries out of nowhere because it is easy to you." Advancement means native HTML/CSS platform features, not polyfills or npm packages. A library needs a concrete reason the platform can't cover, plus its byte and CWV cost, stated before suggesting it.

**How to apply:** Still run modern-web-guidance first. Build the modern API path as the default, keep the plain-HTML behavior as the base that other browsers get, and state the support facts once without arguing against the feature. Design rules in CLAUDE.md (tokens, transitions, no `!important`) still apply. Scope is this project only, not client work. See [[reference-browser-verification]].
