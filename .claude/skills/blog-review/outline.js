// Run on the post preview (?p=ID&preview=true). Returns structure only: headings, block order and link targets.
// The body text comes from get_page_text; full text through javascript_tool gets blocked or cut at about 1,000 characters.
(() => {
  const root = document.querySelector('.wp-block-post-content') || document.querySelector('main');
  const links = [...root.querySelectorAll('a[href]')]
    .filter((a) => !a.closest('sup') && !a.getAttribute('href').startsWith('#'))
    .map((a) => { const u = new URL(a.href); return u.host + u.pathname + (u.search ? '?…' : '') + u.hash; });
  const text = root.innerText;
  return JSON.stringify({
    em: (text.match(/\u2014/g) || []).length,
    en: (text.match(/\u2013/g) || []).length,
    bang: (text.match(/[A-Za-z)]!(\s|$)/g) || []).length,
    words: text.split(/\s+/).filter(Boolean).length,
    headings: [...root.querySelectorAll('h2,h3,h4')].map((h) => h.tagName + ' ' + h.innerText.trim()),
    blocks: [...root.children].map((e) => e.tagName.toLowerCase()).join(' ').replace(/(?:p )+/g, (m) => 'p×' + m.trim().split(' ').length + ' '),
    images: [...root.querySelectorAll('img')].map((i) => (i.alt ? 'alt: ' + i.alt.slice(0, 60) : 'NO ALT')),
    links: [...new Set(links)],
  });
})();
