# Projects get their own pages, and posts link to them through a hidden taxonomy

The `project` post type was registered non-public with no rewrite, on purpose: a project was only ever a card, and a one-sentence page per project would have been thin content in the sitemap and in search. That changes now. Each project gets a page at `/projects/<slug>/` holding a longer write-up, plus a list of every post that writes about it, and those posts link back. All five projects are indexed from day one even though their bodies are still one sentence. Ivan chose simple over a noindex-until-written rule, so expect thin pages in search until the write-ups exist.

Posts point at projects through `ik2_project`, a hidden taxonomy on `post`. It is not public, has no rewrite, archive or query var, and shows as a checklist in the post sidebar. Each published project owns one term (slug = project slug, term meta `project_id`), created and renamed on `save_post_project` and deleted only when the project is deleted for good. Nobody can create or edit terms by hand (`manage_terms`, `edit_terms` and `delete_terms` are `do_not_allow`), so the checklist can't drift from the projects. `SCHEMA_VERSION` in `project-links.php` backfills the terms and flushes rewrite rules on the first request after a deploy.

The cards also became rows. The projects archive groups them under one heading per status, so a status that every project shares stops repeating on every card.

## Considered Options

- **Noindex until the body passes about 150 words.** Keeps thin pages out of search with no toggle to remember. Turned down for now to keep it simple.
- **A custom project picker storing IDs in post meta.** Same result, but needs editor JavaScript and meta queries. The taxonomy gives the UI, REST and `tax_query` for free.
- **Manual `Write-up::URL` links on the project.** Already possible, but one-way: the post never links back.
- **Keep the card, tidied (A) or with a spec table (C).** Turned down for the ledger (B): fewer frames, more projects per screen, and the same pattern as the articles archive.

## Consequences

Trashed or drafted projects keep their term so post links survive, but they drop out of the post backlink until published again. Project pages share the single post's section CSS, lightbox, heading anchors and image sizing through `ARTICLE_POST_TYPES` in `inc/assets.php`. The trail and Yoast's BreadcrumbList read Home > Projects > name. Revisit the indexing choice if Search Console flags the project pages as thin or crawled but not indexed.
