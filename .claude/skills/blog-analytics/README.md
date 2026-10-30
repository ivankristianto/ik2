# blog-analytics

Pulls raw Google Search Console and GA4 data for ivankristianto.com as JSON, so Claude can answer questions about search queries, rankings, clicks and traffic from the real numbers. It only fetches. What to do with the data is decided in the conversation.

It talks to the official APIs as a Google Cloud service account with read-only scopes (`webmasters.readonly`, `analytics.readonly`). There's no browser and no third-party service in between, and nothing to install beyond Node 24.

## Files

| File | What it is |
| :-- | :-- |
| `SKILL.md` | Instructions Claude follows when you run `/blog-analytics`: commands, flags, how to read the numbers. |
| `fetch.mjs` | The script. Signs a token with the service account key, calls the APIs, pages through results, writes JSON. |
| `../../../tests/blog-analytics.test.mjs` | Unit tests for the date, filter and row-shaping helpers (`node --test tests/blog-analytics.test.mjs`). |

## One-time setup

About 15 minutes. You need to be an Owner of the Search Console property and an Administrator of the GA4 property. Everything below is free: neither API needs a billing account.

### 1. Create a Google Cloud project

1. Sign in to https://console.cloud.google.com with a **personal** Google account. Don't use a Workspace work account. Its organization policy usually blocks service account keys (step 4 then fails), and a personal site's credentials don't belong in an employer's Cloud org. The account doesn't have to be the one that owns the Search Console or GA4 property.
2. Open https://console.cloud.google.com/projectcreate.
3. Project name: `ik2-analytics` (anything works). Location: **No organization**.
4. Click **Create**, then pick the new project in the project selector at the top of the console. Every later step must run with this project selected.

### 2. Enable the APIs

Open each link with the project selected and click **Enable**:

- Google Search Console API: https://console.cloud.google.com/apis/library/searchconsole.googleapis.com
- Google Analytics Data API: https://console.cloud.google.com/apis/library/analyticsdata.googleapis.com
- Google Analytics Admin API: https://console.cloud.google.com/apis/library/analyticsadmin.googleapis.com (only `check` uses it, to list your GA4 properties and their IDs)

A newly enabled API can take a few minutes before calls succeed.

### 3. Create the service account

1. Open https://console.cloud.google.com/iam-admin/serviceaccounts and click **Create service account**.
2. Name: `blog-analytics`. Click **Create and continue**.
3. Skip the role and the user access steps: click **Continue**, then **Done**. It needs no project roles, because its access comes from the Search Console and GA4 grants in steps 5 and 6.
4. Copy the account's email from the list. It looks like `blog-analytics@ik2-analytics.iam.gserviceaccount.com`.

### 4. Download a key

1. Click the service account, open the **Keys** tab, then **Add key** > **Create new key** > **JSON** > **Create**. A `.json` file downloads.
2. Move it out of Downloads and lock it down:

   ```bash
   mkdir -p ~/.config/ik2
   mv ~/Downloads/ik2-analytics-*.json ~/.config/ik2/google-sa.json
   chmod 600 ~/.config/ik2/google-sa.json
   ```

Keep the key outside the repo. `.claude/skills/` is tracked by git, so a key dropped next to `fetch.mjs` would end up in a commit.

If the console says key creation is disabled, the project sits under an organization. Go back to step 1 with a personal account and **No organization**.

### 5. Give it access to Search Console

1. Open https://search.google.com/search-console and select the ivankristianto.com property.
2. Go to **Settings** > **Users and permissions** > **Add user**.
3. Email: the service account email from step 3. Permission: **Restricted**. Click **Add**.

If you have both a domain property (`ivankristianto.com`) and a URL-prefix property (`https://www.ivankristianto.com/`), add the user to the one you want to query. The domain property covers every subdomain and protocol, so prefer it.

### 6. Give it access to GA4

1. Open https://analytics.google.com and click **Admin** (the gear, bottom left).
2. Under **Property settings**, open **Property access management**.
3. Click **+** > **Add users**. Email: the service account email. Untick **Notify new users by email**. Role: **Viewer**. Click **Add**.

### 7. Point the script at the key

The script reads its settings from the shell environment or from the repo's `.env` (gitignored). The repo has no `.env` yet, so create one with just this line. Don't copy `.env.example` wholesale: its Docker settings, like `INSTALL_XDEBUG=true`, would change the local dev stack.

```bash
echo 'IK2_GOOGLE_SA_KEY=~/.config/ik2/google-sa.json' >> .env
```

Then ask Claude to run `/blog-analytics check`, or run it yourself:

```bash
node .claude/skills/blog-analytics/fetch.mjs check
```

It prints the service account email, the Search Console sites it can read (`gscSites`) and the GA4 properties it can see (`ga4Properties`). Add the two values to `.env`:

```bash
IK2_GSC_SITE=sc-domain:ivankristianto.com     # the siteUrl exactly as check printed it
IK2_GA4_PROPERTY=123456789                    # the numeric property ID, no "properties/" prefix
```

### 8. Verify

```bash
F=.claude/skills/blog-analytics/fetch.mjs
node $F gsc --days 7 --max-rows 10
node $F ga4 --days 7 --max-rows 10
node $F sitemaps
node $F inspect https://www.ivankristianto.com/
```

All four should print JSON with real rows. If `gsc` works but `sitemaps` or `inspect` returns 403, raise the service account to **Full** in Search Console (step 5).

## Using it

Ask in chat, for example "use /blog-analytics to pull the last 90 days of queries for my WP-CLI posts". Claude runs `fetch.mjs`, writes the JSON to its session scratchpad (never into the repo), and works from that file. `SKILL.md` lists every command and flag.

## Troubleshooting

| Error | Cause and fix |
| :-- | :-- |
| `IK2_GOOGLE_SA_KEY is not set` | Step 7. Check the line is in `.env` at the repo root, or exported in your shell. |
| `403 User does not have sufficient permission` | The service account isn't a user on that property (step 5 or 6), or `IK2_GSC_SITE` / `IK2_GA4_PROPERTY` names a different property. New users can take a few minutes to work. |
| `403 ... API has not been used in project ... or it is disabled` | Step 2 was skipped for that API, or it was enabled in another project. Enable it in the project that owns the service account and wait a few minutes. |
| `token exchange failed: Invalid JWT Signature` | The key was deleted or rotated. Create a new key (step 4). |
| `token exchange failed: ... account not found` | The service account was deleted. Redo steps 3 to 6. |
| `token exchange failed: invalid_grant` with a time message | The Mac's clock is off. Turn on automatic time in System Settings. |
| `gscError` or `ga4Error` in `check` output | Same causes as the 403s above. `check` keeps going so you can see which side is broken. |

## Security

- The key is a long-lived credential. It can only read the properties you added it to in steps 5 and 6, and the script requests read-only scopes.
- To rotate: create a new key (step 4), swap the file, then delete the old key in the service account's **Keys** tab.
- To revoke everything: remove the user in Search Console and GA4, or delete the service account.
- Never paste the key's contents into chat or commit it. The repo's Betterleaks pre-push hook and CI job would flag a private key, but don't rely on that.
