"""Write tokens.css from the theme's theme.json and copy card.css into an image's source folder.

Usage: python3 -I tokens.py <out-dir>
The palette changes (it did in 2026-10), so colors are never copied into the image source by hand.
"""
import json
import pathlib
import shutil
import sys

here = pathlib.Path(__file__).resolve().parent
root = here.parents[2]
settings = json.loads((root / 'wp-content/themes/ik2/theme.json').read_text())['settings']
out = pathlib.Path(sys.argv[1])
out.mkdir(parents=True, exist_ok=True)

lines = [f'  --{c["slug"]}: {c["color"]};' for c in settings['color']['palette']]
lines += [f'  --font-{f["slug"]}: {f["fontFamily"]};' for f in settings['typography']['fontFamilies']]
(out / 'tokens.css').write_text(':root {\n' + '\n'.join(lines) + '\n}\n')
shutil.copy(here / 'card.css', out / 'card.css')
print(f'wrote {out / "tokens.css"} and {out / "card.css"}')
