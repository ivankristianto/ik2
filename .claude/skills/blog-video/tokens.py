"""Write the video's src/tokens.ts from the theme's theme.json, the same source blog-feature-image's tokens.css comes from.

Usage: python3 -I tokens.py <theme.json> <out.ts>
"""
import json
import pathlib
import sys

settings = json.loads(pathlib.Path(sys.argv[1]).read_text())['settings']
colors = {c['slug']: c['color'] for c in settings['color']['palette']}
fonts = {f['slug']: f['fontFamily'] for f in settings['typography']['fontFamilies']}
pathlib.Path(sys.argv[2]).write_text(
    '// Generated from wp-content/themes/ik2/theme.json by .claude/skills/blog-video/tokens.py. Do not edit.\n'
    f'export const color = {json.dumps(colors, indent=1)} as const;\n'
    f'export const font = {json.dumps(fonts, indent=1)} as const;\n'
)
print(f'wrote {sys.argv[2]}')
