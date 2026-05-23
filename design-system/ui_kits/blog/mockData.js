// Mock data for the blog UI kit. Real-feeling posts pulled from the live site.

window.MOCK = {
  topics: ["WordPress", "Performance", "Security", "AI", "Linux", "DevTools"],

  hero: {
    eyebrow: "WEB ENGINEER / WORDPRESS / AI / PERFORMANCE",
    title: "I'm Ivan Kristianto.",
    blurb: "I explore WordPress, AI, performance, and developer tooling to build better experiences on the web. This is where I write things down so I remember them — and so you can use them too.",
  },

  posts: [
    {
      slug: "cloudflare-api-cli-tool",
      title: "Cloudflare API CLI Tool",
      date: "July 8, 2020",
      readingTime: "4 min read",
      excerpt:
        "I use Cloudflare CDN to add a performance and security layer to my websites for free. Most of the time, when I need to change a setting on a zone, I have to log in to the dashboard, sometimes with 2FA. For a small change, that's too many steps. So I built a small Node CLI to manage my account via the Cloudflare API. And it works.",
      tags: ["cli", "cloudflare", "javascript"],
      cover: { bg: "#E0E7FF", label: "cloudflare-api" },
    },
    {
      slug: "secure-your-wordpress-site",
      title: "Secure Your WordPress Site",
      date: "July 29, 2018",
      readingTime: "9 min read",
      excerpt:
        "WordPress is an open-source project developed by a community from all over the world. A lot of experts spend their time making it as secure as possible — but I'm not in the position to say it has bulletproof security. Here are a couple of best practices I use.",
      tags: ["security", "wordpress"],
      cover: { bg: "#F1EFE8", label: "wp-hardening" },
    },
    {
      slug: "why-you-should-always-use-https",
      title: "Why You Should Always Use Site with HTTPS",
      date: "August 4, 2018",
      readingTime: "6 min read",
      excerpt:
        "I noticed my Internet provider being a little 'naughty' in their business practices — they were injecting tracking JavaScript into pages that weren't served over HTTPS. Here's what it looked like and why this is a real problem for users.",
      tags: ["security", "https"],
      cover: { bg: "#FEE2E2", label: "https-everywhere" },
    },
    {
      slug: "optimize-wordpress-litespeed",
      title: "Optimize WordPress Performance with LiteSpeed",
      date: "June 18, 2018",
      readingTime: "7 min read",
      excerpt:
        "I gave a presentation at WordPress Jakarta Meetup #14 about optimising WordPress with LiteSpeed Cache and LiteSpeed Web Server. The results were worth writing down — both for me and for anyone else running WordPress under pressure.",
      tags: ["wordpress", "performance", "litespeed"],
      cover: { bg: "#DCFCE7", label: "litespeed" },
    },
  ],

  guides: [
    {
      slug: "performance",
      eyebrow: "GUIDE · 04 PARTS",
      title: "Make WordPress load faster with LiteSpeed",
      description:
        "A four-part series on cache tuning, edge rules, and image strategy — from start to measurable wins.",
      tags: ["wordpress", "performance"],
    },
    {
      slug: "security",
      eyebrow: "GUIDE · 06 PARTS",
      title: "Secure your WordPress site",
      description:
        "Practical hardening — file permissions, 2FA, logins, headers. Honest about what each step actually buys you.",
      tags: ["security", "wordpress"],
    },
    {
      slug: "docker",
      eyebrow: "GUIDE · 03 PARTS",
      title: "Run WordPress on Docker, for real",
      description:
        "How I migrated this very blog to a full Docker stack — and the boring details that make it survive reboots and updates.",
      tags: ["docker", "wordpress", "devops"],
    },
    {
      slug: "ai-dev-loop",
      eyebrow: "GUIDE · 05 PARTS",
      title: "AI in the developer loop",
      description:
        "Where AI actually saves me time as a senior engineer, and where it costs me time. Honest review of tooling from 2025.",
      tags: ["ai", "devtools"],
    },
  ],

  notes: [
    { date: "2026-04-12", title: "Switched the blog to a system-font stack — instant render, no FOUT", tags: ["performance"] },
    { date: "2026-03-30", title: "A tiny CLI to invalidate Cloudflare cache from a deploy hook", tags: ["cli", "cloudflare"] },
    { date: "2026-03-04", title: "Reading: The Pragmatic Engineer on AI in the dev loop", tags: ["notes", "ai"] },
    { date: "2026-02-18", title: "WordPress 6.7 + PHP 8.3 + LiteSpeed: re-tested every guide", tags: ["wordpress"] },
    { date: "2026-01-22", title: "Cleaned up my dotfiles repo — moved everything to chezmoi", tags: ["linux"] },
  ],

  article: {
    slug: "secure-your-wordpress-site",
    title: "Secure Your WordPress Site",
    date: "July 29, 2018",
    readingTime: "9 min read",
    tags: ["security", "wordpress", "wordpress-security"],
    intro:
      "WordPress is an open-source project developed by a community from all over the world. A lot of experts spend their time making it as secure as possible — but I'm not in the position to say it has bulletproof security. You can see a few vulnerabilities have been reported, fixed, and disclosed via the WordPress HackerOne program. You can report one too: if it's valid, you'll get a bounty.",
    body: [
      { kind: "h2", text: "Start with the boring stuff" },
      { kind: "p", text: "Most WordPress sites I audit are not breached because of a clever zero-day. They are breached because something boring was left undone: a stale plugin, a weak admin password, a forgotten staging environment indexed by Google." },
      { kind: "p", text: "So before any of the more interesting steps below, do these three things." },
      { kind: "ol", items: ["Update core, themes, and plugins.", "Delete plugins and themes you are not using — yes, even the default ones.", "Force every admin account onto 2FA, not just yours."] },
      { kind: "h2", text: "Lock the login page" },
      { kind: "p", text: "The login page is the front door. Two changes make it noticeably harder to brute-force:" },
      { kind: "pre", text: "# In wp-config.php\ndefine( 'WP_DISABLE_FILE_EDIT', true );\ndefine( 'DISALLOW_FILE_MODS', true );" },
      { kind: "callout", variant: "note", title: "Note", text: "If you use a managed host, check whether these constants are already set — some hosts override your wp-config." },
      { kind: "p", text: "Then add a rate-limiter at the edge. I use Cloudflare's Rate Limiting rules. The free tier is enough for a personal site." },
      { kind: "h2", text: "Harden file permissions" },
      { kind: "p", text: "After deployment, your tree should look something like this:" },
      { kind: "pre", text: "# Directories\nfind . -type d -exec chmod 755 {} \\;\n\n# Files\nfind . -type f -exec chmod 644 {} \\;\n\n# wp-config gets a tighter mask\nchmod 600 wp-config.php" },
      { kind: "callout", variant: "outdated", title: "Outdated · 2018", text: "If you are reading this on the 2026 redesign, I have re-tested the steps and they still work. The Cloudflare UI has moved around a bit — links in this post point at the current dashboard." },
      { kind: "h2", text: "Watch the logs" },
      { kind: "p", text: "None of the above matters if you do not look at your logs. Set up a daily digest — Logwatch, a basic cron job, or your host's built-in alerts. You want to notice unusual POST traffic to wp-login.php before someone else does." },
    ],
  },

  resume: {
    name: "Ivan Kristianto",
    title: "Senior Web Engineer · Google Developer Expert (Web)",
    location: "Jakarta, Indonesia · remote",
    summary:
      "I build WordPress at scale at Human Made. Before that I spent six years at 10up. I organise the Jakarta WordPress Meetup and was the lead organiser of WordCamp Jakarta 2017. I write here mostly about performance, security, and developer tooling.",
    experience: [
      { role: "Senior Web Engineer", org: "Human Made", from: "2024", to: "Present", note: "Enterprise WordPress, editorial tooling, performance work for large publishers." },
      { role: "Senior Web Engineer", org: "10up", from: "2018", to: "2024", note: "Custom WordPress for newsrooms and enterprise. Performance audits, Gutenberg block libraries." },
      { role: "Lead Organiser", org: "WordCamp Jakarta 2017", from: "2017", to: "2017", note: "Lead organiser for the largest WordCamp held in Indonesia (so far)." },
      { role: "Lead Organiser", org: "Jakarta WordPress Meetup", from: "2015", to: "Present", note: "Monthly meetup. Talks, workshops, and a community that ships." },
    ],
    skills: [
      { group: "WordPress", items: ["Core, REST API, Gutenberg blocks", "Multisite, VIP-style scale", "Theme & plugin architecture"] },
      { group: "Performance", items: ["LiteSpeed / Nginx / Varnish", "Cloudflare edge rules", "Core Web Vitals audits"] },
      { group: "Stack", items: ["PHP 8.x, MySQL, Redis", "Node.js, TypeScript", "Docker, GitHub Actions"] },
      { group: "Other", items: ["AI in the dev loop", "Talks: WordCamp, Google IO recaps", "Mentoring junior engineers"] },
    ],
  },
};
