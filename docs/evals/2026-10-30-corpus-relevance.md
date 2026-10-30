# Corpus relevance eval, 2026-10-30

How this was produced: `node scripts/corpus-eval.mjs pairs` built 230 blind
(source, candidate) pairs for the 30 Articles in
`tests/fixtures/corpus-eval.json`, from two systems: NEW (top 4 from
`wp_ik2_corpus_related`, nomic-embed-text q8 + BM25, RRF k = 60) and BASELINE
(the old block: newest 4 in the first category). An independent judge model
graded every pair 0, 1 or 2 without seeing which system produced it, and
`node scripts/corpus-eval.mjs report` scored the result. Local corpus: 490
Articles, 777 Chunks, one Run of 171 s at 605 MB peak memory.

Outcome:

- NEW beats BASELINE on every measure: mean grade 1.49 against 0.32, 58%
  against 6% graded 2, 9% against 75% graded 0, nDCG@4 0.952 against 0.176.
- No score floor separates weak picks from good ones. Weak picks score in the
  same band as good ones (the ModSecurity Article's unrelated pick scores
  0.753), and every Article dropped below 4 picks falls back to category
  picks, which are worse. `RELATED_MIN_SCORE` is set to 0.65: it removes the
  single weakest NEW pick in the sample and sends no Article in the corpus to
  the fallback.
- Follow-up worth testing: the floor is applied to cosine while the list is
  ordered by RRF, so a high floor promotes positions 5 to 8. A floor on the
  fused rank, or none at all, may be the better design.

The full generated report follows.

# Corpus relevance eval

30 Articles, 230 blind pairs, 230 graded, 0 missing.
Grades: 0 unrelated, 1 loosely related, 2 a reader of the source would want this next.

## Systems

nDCG@4 uses gain 2^grade - 1 and discount log2(rank + 1). The ideal list for each Article is its best 4 grades pooled across every graded pair for that Article. Articles whose pooled grades are all 0 are left out of nDCG. Ungraded picks count as 0 in nDCG and are left out of the other columns. A list shorter than 4 (an Article alone in its category gets no BASELINE picks) scores the missing slots as 0, which matches the old block hiding itself.

| System | Graded | Ungraded | Mean grade | Share of 2s | Share of 0s | nDCG@4 | Articles in nDCG |
| --- | --- | --- | --- | --- | --- | --- | --- |
| NEW (top 4, no floor) | 120 | 0 | 1.49 | 58% | 9% | 0.952 | 29 |
| BASELINE (newest in first category) | 114 | 0 | 0.32 | 6% | 75% | 0.176 | 29 |
| FINAL @ 0.600 | 119 | 1 | 1.50 | 59% | 8% | 0.952 | 29 |
| FINAL @ 0.750 | 109 | 11 | 1.46 | 61% | 15% | 0.868 | 29 |

## Floor sweep for NEW

Over the 120 graded NEW top-4 picks (11 graded 0, 70 graded 2). An Article needs the category fallback when fewer than 4 of its 8 stored rows clear the floor, which is how get_related() and the theme fill the block.

| Floor | Picks kept | Zeros kept | Twos dropped | Mean grade of kept | Eval Articles needing fallback | All Articles needing fallback |
| --- | --- | --- | --- | --- | --- | --- |
| 0.500 | 120 | 11 | 0 | 1.49 | 0 of 30 | 0 of 490 |
| 0.525 | 120 | 11 | 0 | 1.49 | 0 of 30 | 0 of 490 |
| 0.550 | 120 | 11 | 0 | 1.49 | 0 of 30 | 0 of 490 |
| 0.575 | 119 | 10 | 0 | 1.50 | 0 of 30 | 0 of 490 |
| 0.600 | 119 | 10 | 0 | 1.50 | 0 of 30 | 0 of 490 |
| 0.625 | 119 | 10 | 0 | 1.50 | 0 of 30 | 0 of 490 |
| 0.650 | 119 | 10 | 0 | 1.50 | 0 of 30 | 0 of 490 |
| 0.675 | 118 | 9 | 0 | 1.52 | 0 of 30 | 2 of 490 |
| 0.700 | 117 | 8 | 0 | 1.53 | 1 of 30 | 10 of 490 |
| 0.725 | 112 | 5 | 0 | 1.58 | 3 of 30 | 35 of 490 |
| 0.750 | 95 | 3 | 4 | 1.66 | 6 of 30 | 104 of 490 |
| 0.775 | 68 | 0 | 17 | 1.78 | 18 of 30 | 231 of 490 |
| 0.800 | 39 | 0 | 33 | 1.95 | 24 of 30 | 353 of 490 |
| 0.825 | 30 | 0 | 40 | 2.00 | 27 of 30 | 427 of 490 |
| 0.850 | 26 | 0 | 44 | 2.00 | 27 of 30 | 467 of 490 |

Rule: the recommended floor is the lowest value in the sweep that drops at least 80% of the zero-graded NEW picks while dropping at most 10% of the two-graded ones.

**No floor in the sweep meets the rule.** The closest is 0.750, the floor that drops the most zero-graded picks while dropping at most 10% of the twos. The FINAL simulation below uses it.

## Fixed Articles side by side

### 503: WordCamp Jakarta 2017 Short Story

First category: WordPress

| # | NEW (score, grade) | BASELINE (grade) | FINAL @ 0.750 (source, grade) |
| --- | --- | --- | --- |
| 1 | AMP for WordPress - WordPress Jakarta #15 (0.783, grade 1) | Make WordPress Load Faster With LiteSpeed Cache - Webinar (grade 1) | AMP for WordPress - WordPress Jakarta #15 (new #1 0.783, grade 1) |
| 2 | Speaking at WordCamp Ubud 2017 (0.740, grade 2) | Secure Your WordPress Site (grade 1) | The Perfect WordPress Installation (new #4 0.769, grade 1) |
| 3 | Optimize WordPress Performance with Litespeed (0.732, grade 1) | AMP for WordPress - WordPress Jakarta #15 (grade 1) | WordPress Series: Plugins To Start With (new #5 0.764, ungraded) |
| 4 | The Perfect WordPress Installation (0.769, grade 1) | Speaking at WordCamp Ubud 2017 (grade 2) | WordPress Plugin of the Week: Jetpack (new #7 0.761, ungraded) |

### 494: Secure Your WordPress Site

First category: WordPress

| # | NEW (score, grade) | BASELINE (grade) | FINAL @ 0.750 (source, grade) |
| --- | --- | --- | --- |
| 1 | Simple Way to Secure Your WordPress (0.919, grade 2) | Make WordPress Load Faster With LiteSpeed Cache - Webinar (grade 1) | Simple Way to Secure Your WordPress (new #1 0.919, grade 2) |
| 2 | What To Do When Your Wordpress Blog Got Hacked (0.873, grade 2) | AMP for WordPress - WordPress Jakarta #15 (grade 1) | What To Do When Your Wordpress Blog Got Hacked (new #2 0.873, grade 2) |
| 3 | Secure Your WordPress Blog (0.937, grade 2) | WordCamp Jakarta 2017 Short Story (grade 1) | Secure Your WordPress Blog (new #3 0.937, grade 2) |
| 4 | WordPress 3.0.2 Hardening Security Released (0.862, grade 2) | Speaking at WordCamp Ubud 2017 (grade 1) | WordPress 3.0.2 Hardening Security Released (new #4 0.862, grade 2) |

### 985: [HowTo] Radio Streaming Server in Ubuntu Using IceCast2

First category: Tooling

| # | NEW (score, grade) | BASELINE (grade) | FINAL @ 0.750 (source, grade) |
| --- | --- | --- | --- |
| 1 | [HowTo] Broadcast Radio With Winamp + SHOUTcast to Ubuntu... (0.939, grade 2) | Cloudflare API CLI Tool (grade 0) | [HowTo] Broadcast Radio With Winamp + SHOUTcast to Ubuntu... (new #1 0.939, grade 2) |
| 2 | Zeya: Music Server With HTML 5 (0.731, grade 2) | Secure Your Connection With Your Own Cheap OpenVPN (grade 0) | Cloudflare API CLI Tool (fallback, grade 0) |
| 3 | Step By Step Setup a VPS With WHM/Cpanel (0.749, grade 1) | Migrated Fully to Docker (grade 0) | Secure Your Connection With Your Own Cheap OpenVPN (fallback, grade 0) |
| 4 | Remote Desktop On Ubuntu With TightVNC (0.713, grade 1) | Introducing create-wp-site (grade 0) | Migrated Fully to Docker (fallback, grade 0) |

### 576: Google+ Review and Facebook Comparison

First category: Misc

| # | NEW (score, grade) | BASELINE (grade) | FINAL @ 0.750 (source, grade) |
| --- | --- | --- | --- |
| 1 | Facebook Now Have Video Call (0.793, grade 2) | Why You Should Always Use Site with HTTPS (grade 0) | Facebook Now Have Video Call (new #1 0.793, grade 2) |
| 2 | Facebook Now Become Free Ads And Spam Social Networking (0.766, grade 2) | Ubuntu 16.04 and Android Lollipop (grade 0) | Facebook Now Become Free Ads And Spam Social Networking (new #2 0.766, grade 2) |
| 3 | 10 Apps You Should Have On Blackberry (0.758, grade 0) | Everybody Loves Progress (grade 0) | 10 Apps You Should Have On Blackberry (new #3 0.758, grade 0) |
| 4 | Internet Messenger Service Comparison (0.772, grade 1) | Holiday is coming... (grade 0) | Internet Messenger Service Comparison (new #4 0.772, grade 1) |

### 536: ModSecurity Error: UNSUPPORTED DELAYED Rules: Remote File Injection attempt in ARGS

First category: Misc

| # | NEW (score, grade) | BASELINE (grade) | FINAL @ 0.750 (source, grade) |
| --- | --- | --- | --- |
| 1 | Someone Trying To Inject IvanKristianto.com (0.786, grade 2) | Why You Should Always Use Site with HTTPS (grade 1) | Someone Trying To Inject IvanKristianto.com (new #1 0.786, grade 2) |
| 2 | Letting an AI agent into your hosting, safely (0.753, grade 1) | Ubuntu 16.04 and Android Lollipop (grade 0) | Letting an AI agent into your hosting, safely (new #2 0.753, grade 1) |
| 3 | Free Web Proxy Server To Bypass Access Filter (0.753, grade 0) | Everybody Loves Progress (grade 0) | Free Web Proxy Server To Bypass Access Filter (new #3 0.753, grade 0) |
| 4 | Simple Perl Script To Test Mod_Evasive On Your Apache Web... (0.797, grade 2) | Holiday is coming... (grade 0) | Simple Perl Script To Test Mod_Evasive On Your Apache Web... (new #4 0.797, grade 2) |

### 522: Upgraded to Ubuntu 15.04

First category: Misc

| # | NEW (score, grade) | BASELINE (grade) | FINAL @ 0.750 (source, grade) |
| --- | --- | --- | --- |
| 1 | Disappointing Jaunty Jackalope, New Hope For Karmic Koala (0.749, grade 2) | Why You Should Always Use Site with HTTPS (grade 0) | Ubuntu 16.04 and Android Lollipop (new #2 0.769, grade 2) |
| 2 | Ubuntu 16.04 and Android Lollipop (0.769, grade 2) | Ubuntu 16.04 and Android Lollipop (grade 2) | Back to Ubuntu after so long (new #7 0.768, ungraded) |
| 3 | Pidgin v2.5.8 Update Fix Yahoo Messenger Problem (0.725, grade 1) | Everybody Loves Progress (grade 0) | Ubuntu 10.04 Codename Lucid Lynx Release April 29th 2010 (new #8 0.762, ungraded) |
| 4 | Ubuntu 10.10 Maverick Meerkat Released, What's Next? (0.737, grade 2) | Holiday is coming... (grade 0) | Why You Should Always Use Site with HTTPS (fallback, grade 0) |

### 701: Use And Tweak timthumb Script For WordPress Blog

First category: WordPress

| # | NEW (score, grade) | BASELINE (grade) | FINAL @ 0.750 (source, grade) |
| --- | --- | --- | --- |
| 1 | TimThumb Zero Day Vulnerability (0.858, grade 2) | Make WordPress Load Faster With LiteSpeed Cache - Webinar (grade 1) | TimThumb Zero Day Vulnerability (new #1 0.858, grade 2) |
| 2 | Bring Your WordPress Blog To Local Server For Testing Env... (0.783, grade 1) | Secure Your WordPress Site (grade 1) | Bring Your WordPress Blog To Local Server For Testing Env... (new #2 0.783, grade 1) |
| 3 | Facebook Thumbnail Image (0.795, grade 1) | AMP for WordPress - WordPress Jakarta #15 (grade 1) | Facebook Thumbnail Image (new #3 0.795, grade 1) |
| 4 | Create a Maintenance Page in WordPress (0.782, grade 1) | WordCamp Jakarta 2017 Short Story (grade 1) | Create a Maintenance Page in WordPress (new #4 0.782, grade 1) |

### 1981: Chrome Modern Web Guidance, What is it?

First category: AI

| # | NEW (score, grade) | BASELINE (grade) | FINAL @ 0.750 (source, grade) |
| --- | --- | --- | --- |
| 1 | How to code with AI agents (0.797, grade 2) | How to code with AI agents (grade 2) | How to code with AI agents (new #1 0.797, grade 2) |
| 2 | Letting an AI agent into your hosting, safely (0.790, grade 2) | Learning to build Machine Learning (grade 1) | Letting an AI agent into your hosting, safely (new #2 0.790, grade 2) |
| 3 | Useful Google Chrome Extensions For Web Developer (0.806, grade 1) |  | Useful Google Chrome Extensions For Web Developer (new #3 0.806, grade 1) |
| 4 | Is Your Browser Using the Latest Updates? (0.753, grade 1) |  | Is Your Browser Using the Latest Updates? (new #4 0.753, grade 1) |

## Ungraded picks in the simulated block

- @ 0.600: 893 Technology Role In World Cup 2010 -> 891 TeamViewer, The Best and Free Remote Control Software (new #5 0.708)
- @ 0.750: 503 WordCamp Jakarta 2017 Short Story -> 600 WordPress Series: Plugins To Start With (new #5 0.764)
- @ 0.750: 503 WordCamp Jakarta 2017 Short Story -> 556 WordPress Plugin of the Week: Jetpack (new #7 0.761)
- @ 0.750: 522 Upgraded to Ubuntu 15.04 -> 535 Back to Ubuntu after so long (new #7 0.768)
- @ 0.750: 522 Upgraded to Ubuntu 15.04 -> 929 Ubuntu 10.04 Codename Lucid Lynx Release April 29th 2010 (new #8 0.762)
- @ 0.750: 568 Google Nexus With Android Ice Cream -> 649 New Google Security System: 2 Steps Verification (new #5 0.751)
- @ 0.750: 662 Disable Autorun and Autoplay In Windows 7 -> 829 [HowTo] Show Hidden Files in Windows Vista and Windows 7 (new #7 0.754)
- @ 0.750: 937 Nice And Steady Ubuntu Karmic Koala -> 535 Back to Ubuntu after so long (new #6 0.823)
- @ 0.750: 896 [HowTo] Extract Database Information From Information_sch... -> 888 Backup All Your MySQL Database With One Script (new #7 0.767)
- @ 0.750: 689 Easy To Use Remote Admin Tools -> 891 TeamViewer, The Best and Free Remote Control Software (new #8 0.852)
- @ 0.750: 680 Copy Files Faster On Windows -> 950 Filehippo.com - Download All Windows Best Free Software I... (new #8 0.756)
- @ 0.750: 490 All the Web Talks from Google IO 2019 -> 924 HTML5 The Next Generation Of Web (new #7 0.798)


