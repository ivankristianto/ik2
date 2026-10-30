---
name: blog-analytics
description: Pull raw Google Search Console and GA4 data for ivankristianto.com as JSON (search queries, pages, clicks, impressions, CTR, position, page views, sessions, URL indexing status, sitemaps) through the official APIs with a read-only service account. Fetches data only; Ivan says in chat what to do with it. Use when Ivan asks for Search Console or Google Analytics numbers, GSC or GA4 data, search queries, traffic by page, indexing status of a URL, or types /blog-analytics.
user-invocable: true
---

# Search Console and GA4 data

`fetch.mjs` calls the Search Console and GA4 APIs as a service account and writes the rows as JSON. Node 24, no packages. It only reads: the scopes are `webmasters.readonly` and `analytics.readonly`.

This skill gets data. Don't write a report, a ranking or recommendations unless Ivan asks for them in the conversation. Answer the question he asked with the numbers, and say which date range and filters they came from.

## Run it

Always pass `--out` with a path in the scratchpad. Never write data into the repo. The script prints one summary line (row count, actual date range) to stderr. Read the file with `jq` or `node -e` and aggregate there instead of loading thousands of rows into context.

```bash
F=.claude/skills/blog-analytics/fetch.mjs
node $F check                                                   # what the service account can see
node $F gsc --days 28 --out <scratchpad>/gsc.json               # query x page, last 28 final days
node $F gsc --dimensions page --start 2026-01-01 --end 2026-03-31 --out <scratchpad>/q1-pages.json
node $F gsc --dimensions query --filter page:contains:/wp-cli --out <scratchpad>/wp-cli.json
node $F ga4 --dimensions pagePath --metrics screenPageViews,activeUsers --days 90 --out <scratchpad>/ga4.json
node $F inspect https://www.ivankristianto.com/some-post/ --out <scratchpad>/inspect.json
node $F sitemaps --out <scratchpad>/sitemaps.json
```

| Option | Applies to | Effect |
| :-- | :-- | :-- |
| `--days N` | gsc, ga4 | Range length, default 28. Counts back from `--end`. |
| `--start`, `--end` | gsc, ga4 | `YYYY-MM-DD`. Default end is 3 days ago for gsc (Search Console finalises late) and yesterday for ga4. |
| `--dimensions a,b` | gsc, ga4 | gsc: `query`, `page`, `date`, `country`, `device`, `searchAppearance`; default `query,page`. ga4: any [GA4 dimension](https://developers.google.com/analytics/devguides/reporting/data/v1/api-schema); default `pagePath`. |
| `--metrics a,b` | ga4 | Default `screenPageViews,activeUsers,sessions`. Rows are sorted by the first one, descending. |
| `--filter dim:op:value` | gsc, ga4 | Repeatable, ANDed. `op` is `equals`, `notEquals`, `contains`, `notContains`, `regex`, `notRegex` (RE2, partial match). |
| `--type` | gsc | `web` (default), `image`, `video`, `news`, `discover`, `googleNews`. |
| `--data-state` | gsc | `final` (default) or `all` to include the last few unfinalised days. |
| `--max-rows N` | gsc, ga4 | Default 100000. The script pages through the API until it has them all. |
| `--body file.json` | gsc, ga4 | Merged over the generated request body, for anything the flags don't cover (GA4 `metricFilter`, `orderBys`, a second `dateRanges` entry). Write the file to the scratchpad. |
| `--site`, `--property` | all | Override `IK2_GSC_SITE` / `IK2_GA4_PROPERTY` for one run. |

To compare periods, run the same command twice with different dates and diff the two files.

## Reading the data

- Search Console `ctr` is a fraction (0.034 is 3.4%), and `position` is the average rank, 1 being the top.
- Search Console leaves out anonymised queries, so summing a `query` pull gives fewer clicks than a `page` or `date` pull over the same range. Use the `page` or `date` totals when Ivan asks for site totals.
- Search Console `page` values are full URLs, while GA4 `pagePath` is the path only. Strip `https://www.ivankristianto.com` before joining the two.
- GA4 `totalRows` in the output is the number of rows the report has. If it's larger than `rowCount`, raise `--max-rows`.
- URL Inspection allows 2,000 calls a day per property. Inspect the URLs Ivan names, not the whole sitemap.

## Configuration

Read from the shell environment, or from the repo's `.env` (gitignored; see `.env.example`):

| Variable | Value |
| :-- | :-- |
| `IK2_GOOGLE_SA_KEY` | Path to the service account JSON key, e.g. `~/.config/ik2/google-sa.json`. Keep the key outside the repo: `.claude/skills/` is tracked. |
| `IK2_GSC_SITE` | The property string exactly as `check` lists it: `sc-domain:ivankristianto.com` or `https://www.ivankristianto.com/`. |
| `IK2_GA4_PROPERTY` | The numeric property ID from `check`. |

Never print the key, copy it into the scratchpad or paste its contents into chat.

## Setup and errors

The one-time Google Cloud setup (project, APIs, service account, key, Search Console and GA4 access) is in `README.md` next to this file. If `IK2_GOOGLE_SA_KEY` is missing, or a call fails with a 403, an "API disabled" message or a token error, find the error in the README's Troubleshooting table and tell Ivan which step to fix. The steps in the Cloud console and the Search Console and GA4 admin screens are his to click through. Don't attempt them for him.

## Changing the script

The pure helpers (date ranges, filters, row flattening) are covered by `node --test tests/blog-analytics.test.mjs`. Run it after any change, then run `check` and a small `gsc --days 7` pull against the live APIs.
