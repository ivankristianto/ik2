# Corpus dashboard

## Goal

A wp-admin screen for the Corpus that shows what `wp ik2 corpus status` shows
and lets an admin re-queue one Article or every Article, without a shell on
the Dokploy host. Builds on `2026-10-30-embedding-corpus-design.md`.

## Decisions

| # | Decision |
| --- | --- |
| Q1 | Tools → Corpus (`add_management_page`), `manage_options` only. |
| Q2 | Shows the related on/off switch with a toggle, Documents by status, parked Documents with their error and a Retry button, Chunk/vector/related counts, and the last 10 Runs. |
| Q3 | One Article is re-queued from a Re-embed row action on the Posts list, the Retry button on a parked row, or a post ID field on the dashboard. All three do what `enqueue <id> --force` does. |
| Q4 | Re-queueing does not embed. WordPress can't start the worker, so the screen says the Article embeds at the next Run and shows when that is expected under the hourly schedule in the plugin README. It is labelled as expected, because the cron lives in Dokploy. |
| Q5 | Queue all runs in batches of 50 through `POST ik2/v1/corpus/queue-all`, with a progress bar. The cursor is the last post ID, so a post published mid-way can't shift the batches. The last batch also marks Documents whose Article is gone for removal, as `enqueue --all` does. |
| Q6 | Server-rendered PHP with WP admin markup, `admin-post.php` actions with nonces, and one small plain JS file for the queue-all loop. No build step. |
| Q7 | A Corpus column on the Posts list shows each Article's status, from one query per list page. |

## Shape

```
wp-content/plugins/ik2/
├── inc/corpus/
│   ├── report.php       # status_counts(), parked_documents(), table_counts(), recent_runs(), document_statuses()
│   ├── queue.php        # + published_article_ids(), remove_missing(), queue_batch()
│   ├── batch.php        # pure: next_run_at(), merge_counts(); no WordPress, unit-tested
│   └── admin.php        # the screen, admin-post actions, REST route, Posts column and row action
├── inc/cli/class-corpus-command.php   # status and enqueue --all call the shared functions
└── assets/corpus-admin.js             # queue-all loop over wp.apiFetch
```

The CLI and the screen read the same functions, so they can't disagree.

## Actions

| Action | Where | Does |
| --- | --- | --- |
| `ik2_corpus_reembed` | row action, Retry, ID field | `sync_post( $id, true )`, then redirects back with a notice |
| `ik2_corpus_related` | toggle on the screen | sets `ik2_corpus_related` to on or off |
| `POST ik2/v1/corpus/queue-all` `{ after }` | Queue all button | syncs the next 50 published Articles with ID > `after`; returns `{ counts, after, done, total }`; on `done` runs `remove_missing()` |

Every action checks `manage_options` and a nonce.

## Failure behaviour

When the tables are missing, the screen still renders: empty tables and a line
saying the tables aren't there yet. Times in the Corpus tables are UTC and are
shown in the site timezone.

## Out of scope

Starting a Run from WordPress, a block-editor panel, and per-Article previews
of the related picks.
