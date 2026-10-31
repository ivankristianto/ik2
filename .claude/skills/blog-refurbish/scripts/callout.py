"""Turn drafts/<slug>/update.md into the "Updated" callout block markup, written to callout.html beside it."""
import html
import re
import sys
from pathlib import Path


def inline(text):
    text = html.escape(text, quote=False)
    text = re.sub(r'`([^`]+)`', r'<code>\1</code>', text)
    return re.sub(r'\[([^\]]+)\]\((https://[^)]+)\)', lambda m: '<a href="%s">%s</a>' % (m.group(2), m.group(1)), text)


def callout(md):
    chunks = md.strip().split('\n\n')
    parts = ['<!-- wp:paragraph {"className":"ik-callout__title"} -->\n<p class="ik-callout__title">%s</p>\n<!-- /wp:paragraph -->' % chunks[0].strip()]
    for chunk in chunks[1:]:
        lines = chunk.strip().split('\n')
        if all(line.startswith('- ') for line in lines):
            items = ''.join('<!-- wp:list-item -->\n<li>%s</li>\n<!-- /wp:list-item -->' % inline(line[2:]) for line in lines)
            parts.append('<!-- wp:list -->\n<ul class="wp-block-list">%s</ul>\n<!-- /wp:list -->' % items)
        else:
            parts.append('<!-- wp:paragraph -->\n<p>%s</p>\n<!-- /wp:paragraph -->' % inline(' '.join(lines)))
    return ('<!-- wp:group {"className":"is-style-callout-updated","layout":{"type":"default"}} -->\n'
            '<div class="wp-block-group is-style-callout-updated">' + '\n\n'.join(parts) + '</div>\n<!-- /wp:group -->\n\n')


if __name__ == '__main__':
    if len(sys.argv) < 2:
        sys.exit('usage: callout.py <drafts/slug/update.md> [--stdout]')
    src = Path(sys.argv[1])
    if not src.read_text().startswith('Updated '):
        sys.exit('first line of %s must be "Updated <Month D, YYYY>"' % src)
    out = callout(src.read_text())
    if '--stdout' in sys.argv:
        sys.stdout.write(out)
    else:
        (src.parent / 'callout.html').write_text(out)
        print(src.parent / 'callout.html')
