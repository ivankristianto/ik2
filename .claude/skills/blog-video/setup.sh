#!/usr/bin/env bash
# Run preflight, then copy the Remotion template into a post's gitignored video folder, write tokens from theme.json and install.
# Usage: setup.sh <slug> [dest]   Run on the host: Kokoro and Remotion's Chrome are not in the tools container.
set -euo pipefail
[ $# -ge 1 ] || { echo "usage: setup.sh SLUG [DEST]" >&2; exit 2; }

here=$(cd "$(dirname "$0")" && pwd)
root=$(cd "$here/../../.." && pwd)
dest=${2:-"$root/.claude/blog/drafts/$1/video"}

"$here/preflight.sh"

if [ -e "$dest/package.json" ]; then
  echo "keeping existing project in $dest, refreshing tokens only"
else
  mkdir -p "$dest"
  cp -R "$here/template/." "$dest/"
  # Start every project as a draft of this slug: the draft outro shows no URL, so a forgotten edit can't point at another post.
  python3 -I -c 'import json,sys; p=sys.argv[1]; v=json.load(open(p)); v["post"].update(slug=sys.argv[2], status="draft"); open(p,"w").write(json.dumps(v,indent="\t")+"\n")' "$dest/src/video.json" "$1"
fi
python3 -I "$here/tokens.py" "$root/wp-content/themes/ik2/theme.json" "$dest/src/tokens.ts"
(cd "$dest" && npm install --no-audit --no-fund --loglevel=error && npx remotion browser ensure >/dev/null)
echo "$dest"
