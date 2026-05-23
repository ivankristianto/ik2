# PRODUCT.md

## Project

**Name:** ivankristianto.com — Ivan Kristianto's personal engineering blog
**Tagline:** Passionately Share and Learn.
**Register:** brand

The site is a writer's room for a working web engineer. Long-form posts on WordPress, performance, security, AI, Linux, and developer tooling, plus shorter notes, guides, and a public resume. The design is the product: a calm, technical reading surface that makes the author look more like a senior engineer and less like a marketing site.

## Users

**Primary:** working web engineers and WordPress developers who land on a specific post via search or a shared link. They read one or two articles, glance at the about page, sometimes subscribe.

**Secondary:** conference organisers, hiring managers, and meetup attendees who want to verify Ivan's background — Senior Web Engineer at Human Made, Google Developer Expert in Web Technology, lead organiser of Jakarta WordPress Meetup, WordCamp Jakarta organiser.

**What they want:**

- The article, fast, with code blocks that copy cleanly and images that don't pop layout.
- A sense that the author is a real engineer with real receipts (tool names, versions, commands, screenshots).
- Easy access to RSS, GitHub, LinkedIn, X — they leave when they're done; the site shouldn't beg.

**What they don't want:**

- Newsletter modals, exit-intent popups, "subscribe to my premium content" CTAs.
- Marketing scaffolding ("Welcome to my blog where I share my journey").
- Animated heroes, parallax scroll, gradient orbs, glassy nav.

## Product purpose

Ivan publishes when he has something specific to share — a Cloudflare CLI he built, a WordPress security walkthrough, notes from a talk he gave. The product purpose is to **lower the friction of hitting publish**: the design has to be decided once, then disappear, so writing is the only remaining work. Everything visible on the site should serve reading or navigating to more reading. Nothing else.

The site also functions as a **credibility surface**. When someone Googles "Ivan Kristianto," the site is the canonical reference: resume, talks, projects, contact. The visual register has to read as "senior engineer with taste," not "personal blog with theme."

## Voice

First-person, working-engineer, conversational. Ivan writes the way he'd explain something to a colleague over coffee — short setup, a problem he hit, the fix, sometimes a screenshot, sometimes a code block.

**Voice rules:**

- First person (I), addressing you (the reader). "I use Cloudflare CDN…", "You can see…"
- Conversational, not corporate. No "we are delighted to announce." No "in this article we will explore."
- Specific, with receipts. Real tool names, real versions, real commands, real screenshots. Code blocks beat prose where possible.
- Honest about limits. "I'm not in the position to say it has bulletproof security." Owns gaps; doesn't pretend.
- English is a second language. When ghostwriting, lean naturally idiomatic. Don't imitate slips; just keep the register low-key.

**Casing and punctuation:**

- Sentence case for body and most UI. Title Case for post titles and primary nav.
- Tags and metadata lowercase: `wordpress`, `security`, `cli`, `cloudflare`.
- Straight quotes only. No em-dash flourishes; commas, colons, periods, parentheses do the work.
- No exclamation marks unless something genuinely deserves one.
- Code, paths, commands always in monospace.
- Dates in monospace, full month + day + year: `July 8, 2020`.

**CTAs:** verb-first, no period. **Browse Articles** · **Read Resume** · **Read more** · **Subscribe via RSS**.

**Headlines:** direct, not clever. "Secure Your WordPress Site." "Cloudflare API CLI Tool." Avoid clickbait, avoid colons-as-decoration, avoid "The Ultimate Guide to…".

## Anti-references

What this site explicitly is not, and what we will not build it toward:

- **Medium / Substack newsletter aesthetic.** No serif drop-cap intros, no "claps", no "5 min read · Member-only" gates.
- **SaaS marketing landing pages.** No "Trusted by" logo strips, no gradient-orb heroes, no feature-grid cards with rounded icons.
- **AI tool launch pages.** No glassmorphism, no neon-on-dark, no animated mesh gradients, no "Built with Claude" badge styling reused as decoration.
- **Personal blog clichés.** No author photo on every page, no "Hi, I'm…" hero, no Instagram-style filtered photography.
- **Marketing exuberance.** No exclamation marks in chrome, no emoji in nav, no "🎉 Now live!" banners.
- **Animation as decoration.** No bounces, no scale-in entrances, no scroll-driven parallax, no Lenis smooth-scroll.
- **Borrowed iconography to fill space.** If the design feels empty, widen margins or tighten type — never add an icon next to a heading to make it feel "designed."
- **Side-stripe borders, gradient text, hero-metric templates, identical card grids.** The category-reflex traps for engineering blogs and personal portfolios.

## Strategic principles

1. **The page is the product.** Every visual decision either serves reading or it gets cut. There is no separate "marketing site" register that's allowed to be louder.
2. **Borders and whitespace do the hierarchy work.** Not shadows, not gradients, not imagery. If a design feels flat and you're tempted to add a drop shadow, widen the margins instead.
3. **One accent color, used rarely.** Terracotta `#C2410C` is the single accent — links, primary CTA, focus rings, active states, tag-on-hover. It appears on ≤10% of any given screen. Its rarity is the point.
4. **System fonts only.** No webfont download. The site loads instantly. The brand identity comes from type contrast (sans vs mono) and spacing, not from a $200 license.
5. **Mono is the engineering signal.** Dates, tags, metadata, eyebrows, code: all mono. The mono/sans contrast is the visual personality of the site.
6. **Article max-width 720px.** Wider feels like documentation. Narrower feels precious.
7. **Light first, dark mirrors GitHub.** Warm paper `#F8F7F3` is the page; pure white is reserved for cards. Dark mode uses GitHub's Terminal `#0D1117` for engineers who live in dark mode.
8. **Focus-visible is non-negotiable.** `2px solid` accent ring, `outline-offset: 3px`. Keyboard users come first; the sharp focus ring also reads as engineering rigor.
9. **No emoji in chrome.** Body content is the writer's call.

## Notes for AI agents

When generating new pages, components, or copy for this project:

- The active token slug is `signal` but the color it carries is **Terracotta** `#C2410C`, not the older Signal Blue `#2563EB` found in some legacy references (`design-system/README.md`, `design-system/colors_and_type.css`). The WordPress theme at `wp-content/themes/ik2/theme.json` is the source of truth. Match it; don't reintroduce the old blue.
- Block templates live at `wp-content/themes/ik2/templates/*.html` and `parts/*.html`. Prefer block templates over PHP templates. PHP infrastructure lives under `inc/` in the `IK2\Theme` namespace.
- If you add a token, add it to all three locations: `design-system/colors_and_type.css`, `design-system/theme.json`, and `wp-content/themes/ik2/theme.json`. There is no sync script yet.
- The `design-system/ui_kits/blog/` prototype and the production WP theme are **diverged**. Decide which you're editing and propagate intentionally; do not assume a change in one carries to the other.
