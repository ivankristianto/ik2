#!/usr/bin/env bash
# Render an HTML or SVG source to a 2x PNG and WebP with playwright-cli, served over http (it can't open file://).
# Usage: render.sh <source.html|source.svg> <width> <height>   Writes <source>.png and <source>.webp next to it.
set -euo pipefail
[ $# -eq 3 ] || { echo "usage: render.sh SOURCE WIDTH HEIGHT" >&2; exit 2; }

src=$(cd "$(dirname "$1")" && pwd)/$(basename "$1")
dir=$(dirname "$src")
base="${src%.*}"
w=$2
session=blog-feature-image-$$
h=$3

case "$src" in *.svg) xmllint --noout "$src" ;; esac

port=$(python3 -I -c 'import socket; s=socket.socket(); s.bind(("127.0.0.1", 0)); print(s.getsockname()[1])')
python3 -I -m http.server "$port" --bind 127.0.0.1 --directory "$dir" >/dev/null 2>&1 &
server=$!
trap 'kill $server 2>/dev/null; playwright-cli -s=$session close >/dev/null 2>&1 || true' EXIT
sleep 0.5

playwright-cli -s=$session open about:blank >/dev/null
playwright-cli -s=$session run-code "async page => {
  const ctx = await page.context().browser().newContext({ viewport: { width: $w, height: $h }, deviceScaleFactor: 2 });
  const p = await ctx.newPage();
  await p.goto('http://127.0.0.1:$port/$(basename "$src")');
  await p.evaluate(() => document.fonts.ready);
  await p.screenshot({ path: '$base.png' });
  await ctx.close();
}" >/dev/null

magick "$base.png" -strip "$base.png"
magick "$base.png" -strip -quality 88 "$base.webp"
magick identify -format '%f %wx%h %b\n' "$base.png" "$base.webp"
