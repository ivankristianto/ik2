# Browser steps for applying a refresh

All of this runs with the claude-in-chrome `javascript_tool` in a logged-in tab on https://www.ivankristianto.com/wp-admin/. `wp.apiFetch` sends the nonce for you. The code below is what refreshed posts 626, 897, 845, 949 and 572 on 2026-10-30.

## Gotchas

- **Query-string URLs block output.** The Chrome tool refuses to return text that contains a URL with a query string, so you usually can't read `content.raw` back out. Keep the backup in `window.__ik2bk` and rely on the byte-for-byte checks, which return booleans.
- **Helpers don't survive a reload.** `window.__ik2*` lives only as long as the page. Navigating the tab to an edit screen wipes it, so finish the content and media steps before the Yoast step.
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

Inject a file input:

```js
let inp = document.getElementById('ik2-upload');
if (!inp) { inp = document.createElement('input'); inp.type = 'file'; inp.multiple = true; inp.id = 'ik2-upload';
  inp.setAttribute('aria-label', 'ik2 card upload'); inp.style.cssText = 'position:fixed;top:0;left:0;z-index:999999;';
  document.body.appendChild(inp); }
'ok'
```

Copy each card to the scratchpad under its upload name (`<keyphrase-slug>-<year>-update.webp`). Then `file_upload` those paths onto `#ik2-upload` (find its ref with `find` or `read_page`). Then:

```js
const M = [{file: 'seoquake-2026-update.webp', post: 626, alt: "<contents of alt.txt>"}];
const files = [...document.getElementById('ik2-upload').files];
const out = [];
for (const m of M) {
  try {
    const f = files.find(x => x.name === m.file); if (!f) { out.push({post: m.post, err: 'no file'}); continue; }
    const media = await wp.apiFetch({path: '/wp/v2/media', method: 'POST', body: f,
      headers: {'Content-Disposition': `attachment; filename="${m.file}"`, 'Content-Type': f.type || 'image/webp'}});
    await wp.apiFetch({path: `/wp/v2/media/${media.id}`, method: 'POST', data: {alt_text: m.alt, post: m.post}});
    await wp.apiFetch({path: `/wp/v2/posts/${m.post}`, method: 'POST', data: {featured_media: media.id}});
    const chk = await wp.apiFetch({path: `/wp/v2/posts/${m.post}?context=edit&_fields=status,featured_media`});
    const mchk = await wp.apiFetch({path: `/wp/v2/media/${media.id}?context=edit&_fields=alt_text,media_details`});
    out.push({post: m.post, media: media.id, status: chk.status, featuredOk: chk.featured_media === media.id,
      altOk: mchk.alt_text === m.alt, w: mchk.media_details?.width, h: mchk.media_details?.height});
  } catch (e) { out.push({post: m.post, err: e.code || e.message}); }
}
document.getElementById('ik2-upload')?.remove();
JSON.stringify(out)
```

The size should come back as 2400x1256, because the card renders at 2x. Never delete the old media.

## 4. Yoast fields

Navigate the tab to `https://www.ivankristianto.com/wp-admin/post.php?post=<ID>&action=edit` after step 3 and wait for the editor. Never reuse an edit tab opened before the callout went in: saving it writes the old content back. Run this guard before touching Yoast, and stop unless `ok` is `true`:

```js
await new Promise(r => setTimeout(r, 3000));
const ed = wp.data.select('core/editor');
const g = {dirty: ed.isEditedPostDirty(), hasCallout: ed.getEditedPostContent().includes('callout-updated'),
  featured: ed.getEditedPostAttribute('featured_media')};
JSON.stringify({...g, ok: !g.dirty && g.hasCallout && g.featured === <new media ID>})
```

The apply, score and save code is in blog-seo steps 5 and 6.

- To make a `noindex` post indexable again, call `wp.data.dispatch('yoast-seo/editor').setNoIndex('0')`. Also set `#yoast_wpseo_meta-robots-noindex` to `'0'` before the save.
- After saving, read `yoast_wpseo_focuskw` and `yoast_wpseo_meta-robots-noindex` from the edit page HTML (`fetch` + `DOMParser`). REST doesn't expose either one.
