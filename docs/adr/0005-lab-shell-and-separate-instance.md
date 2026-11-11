# Lab entries live in WordPress, the tools run on a separate instance

The site gets a `/lab` section for small tools and experiments. Each one has a page here (`lab` post type, `/lab/<slug>/`), indexed like a project, with the posts written about it listed as related notes. The tool itself is not in this repo and not in these images. It runs on its own instance at `lab.ivankristianto.com`, from its own repo with its own releases, and the entry page shows it in a sandboxed iframe. Ivan maintains the lab apart from the app, and a tool may not be PHP at all.

The iframe is cross-origin on purpose. Lab tools render input nobody here controls: pasted CSS, metadata and images fetched from other sites. On `www` that would sit on the same origin as the wp-admin session. On the lab origin, a tool that gets tricked can reach nothing of WordPress. The CSP is still Report-Only and covers scripts only, so it is no substitute.

The tool URL is post meta, checked in PHP against one configured origin before any frame is printed. An entry with no URL is a plain write-up, which covers experiments whose output is a results table. Adding a tool is content work in wp-admin and needs no release of this repo.

## Considered Options

- **A block per tool in the ik2 plugin.** Fine for browser-only tools, but every tweak rides a site release, the tool shares the admin origin, and it rules out anything that needs a server or native binaries.
- **`services/lab/` in this repo, as a fifth image.** The embedder is the precedent, but it shares `IMAGE_TAG` because it must match the plugin's tables. Lab tools have no such tie, and each new language would add a toolchain to the blog's CI.
- **Routing `/lab/<slug>/app/` to the lab instance on the main hostname.** Nicer URLs and no iframe, but it puts the tools back on the admin origin.
- **No WordPress shell, a static site on the subdomain alone.** Loses the sitemap, Yoast fields, the site chrome and the links between entries and posts.

## Consequences

Search engines read the WordPress page, so every entry needs real prose: the frame's content doesn't count for it. Each tool has to report its height with a `postMessage` (`ik2-lab:resize`), send `frame-ancestors` for `www`, and open its links in new tabs, since the sandbox blocks top navigation. The design tokens reach the lab repo as a copy of `design-system/tokens.css`, which can drift between releases. If a `frame-src` or `default-src` directive is ever added to the CSP, the lab origin has to be in it. The post-to-entry link code is shared with projects (`entry-links.php`), so a change there affects both.
