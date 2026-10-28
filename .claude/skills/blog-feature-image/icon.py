"""Print an icon as inline SVG that takes currentColor, ready to paste into a card.

Usage: python3 -I icon.py lucide <name> [size]     e.g. lucide square-terminal 24
       python3 -I icon.py simple <slug> [size]     e.g. simple googlechrome 30
Lucide (https://lucide.dev/icons) for UI marks, Simple Icons (https://simpleicons.org) for product logos.
Versions are pinned so a card rebuilt later draws the same glyph.
"""
import re
import sys
import urllib.request

SOURCES = {
    'lucide': 'https://unpkg.com/lucide-static@0.460.0/icons/{}.svg',
    'simple': 'https://cdn.jsdelivr.net/npm/simple-icons@13.21.0/icons/{}.svg',
}

kind, name = sys.argv[1], sys.argv[2]
size = sys.argv[3] if len(sys.argv) > 3 else '24'
req = urllib.request.Request(SOURCES[kind].format(name), headers={'User-Agent': 'Mozilla/5.0 blog-feature-image'})
svg = urllib.request.urlopen(req, timeout=20).read().decode()

svg = re.sub(r'<!--.*?-->|<title>.*?</title>', '', svg, flags=re.S)
# Only the root tag loses its size; inner <rect> width/height are part of the drawing.
svg = re.sub(r'<svg[^>]*>', lambda m: re.sub(r'\s(width|height|class|role)="[^"]*"', '', m.group(0)), svg, count=1)
attrs = f'class="ico" width="{size}" height="{size}" aria-hidden="true"'
if kind == 'simple':
    attrs += ' fill="currentColor"'
print(re.sub(r'\s+', ' ', svg.replace('<svg', f'<svg {attrs}', 1)).strip())
