# Browser steps for applying a refresh

All of this runs with the claude-in-chrome `javascript_tool` in a logged-in tab on https://www.ivankristianto.com/wp-admin/. `wp.apiFetch` sends the nonce for you. The code below is what refreshed posts 626, 897, 845, 949 and 572 on 2026-10-30.

## Gotchas

- **Query-string URLs block output.** The Chrome tool refuses to return text that contains a URL with a query string, so you usually can't read `content.raw` back out. Keep the backup in `window.__ik2bk` and rely on the byte-for-byte checks, which return booleans.
- **Helpers don't survive a reload.** `window.__ik2*` lives only as long as the page. Navigating the tab to an edit screen wipes it, so finish the content step before the Yoast step.
- **Failed calls can be transient.** One callout save failed once on 2026-10-30 and went through on retry. Before retrying, check whether the post changed: compare it with the backup and look for `callout-updated`.
- **Use JSON strings for the callout.** Pass each callout as a JSON string literal (`json.dumps` of `callout.html`), so quotes and newlines come through exactly.

## 1. Back up

```js
const ids = [626, 897];
window.__ik2bk = window.__ik2bk || {};
const out = {};
for (const id of ids) {
  const p = await wp.apiFetch({path: `/wp/v2/posts/${id}?context=edit&_fields=id,status,modified,featured_media,content.raw`});
  window.__ik2bk[id] = p.content.raw;
  out[id] = {status: p.status, modified: p.modified, featured: p.featured_media, len: p.content.raw.length, hasCallout: p.content.raw.includes('callout-updated')};
}
JSON.stringify(out)
```

Write down each post's `featured` ID. The report lists it as the old image.

## 2. Prepend the callout

```js
window.__ik2apply = async (id, callout, allowExisting = false) => {
  const cur = (await wp.apiFetch({path: `/wp/v2/posts/${id}?context=edit&_fields=content.raw`})).content.raw;
  if (cur !== window.__ik2bk[id]) return {id, err: 'content changed since backup'};
  if (cur.includes('callout-updated') && !allowExisting) return {id, err: 'already has callout'};
  const want = callout + cur;
  await wp.apiFetch({path: `/wp/v2/posts/${id}`, method: 'POST', data: {content: want}});
  const after = await wp.apiFetch({path: `/wp/v2/posts/${id}?context=edit&_fields=status,content.raw`});
  return {id, status: after.status, exact: after.content.raw === want, suffixIntact: after.content.raw.endsWith(window.__ik2bk[id])};
};
let r; try { r = await window.__ik2apply(626, "<json string of callout.html>"); } catch (e) { r = {err: e.code || e.message, status: e.data?.status}; }
JSON.stringify(r)
```

Go on only when `exact` and `suffixIntact` are both `true` and `status` hasn't changed.

To stack a new callout above an existing one (only when Ivan asked in step 1), pass `true` as the third argument.

To replace an existing callout (also only when Ivan asked in step 1), cut `cur` from its start to the first `<!-- /wp:group -->\n\n` after it. Check that the cut-off part contains `is-style-callout-updated`, and run the same checks against the remaining suffix.

## 3. Upload the card and set it as featured

Not in the browser any more: use blog-wp-draft's `feature` command over REST, as SKILL.md step 3 shows. It also makes Yoast's `og:image` follow the new card, which the browser upload didn't check. Never delete the old media.

## 4. Noindex and Yoast's scores

The Yoast fields themselves go over REST (SKILL step 8.4). This tab is only for reading Yoast's scores (blog-seo step 6) and, where Ivan approved it, removing noindex.

Navigate the tab to `https://www.ivankristianto.com/wp-admin/post.php?post=<ID>&action=edit` after step 3 and wait for the editor. Never reuse an edit tab opened before the callout went in: saving it writes the old content back. Run this guard first, and stop unless `ok` is `true`:

```js
await new Promise(r => setTimeout(r, 3000));
const ed = wp.data.select('core/editor');
const g = {dirty: ed.isEditedPostDirty(), hasCallout: ed.getEditedPostContent().includes('callout-updated'),
  featured: ed.getEditedPostAttribute('featured_media')};
JSON.stringify({...g, ok: !g.dirty && g.hasCallout && g.featured === <new media ID>})
```

To make a `noindex` post indexable again, set it and save:

```js
wp.data.dispatch('yoast-seo/editor').setNoIndex('0');
document.getElementById('yoast_wpseo_meta-robots-noindex').value = '0';
await wp.data.dispatch('core/editor').savePost();
// Yoast's fields ride on the meta box save; wait for it, not only for the post.
await new Promise(r => { const t = () => (wp.data.select('core/editor').isSavingPost() ||
  wp.data.select('core/edit-post').isSavingMetaBoxes()) ? setTimeout(t, 300) : r(); setTimeout(t, 800); });
'saved'
```

After saving, read `yoast_wpseo_meta-robots-noindex` from the edit page HTML (`fetch` + `DOMParser`). REST doesn't expose it.
