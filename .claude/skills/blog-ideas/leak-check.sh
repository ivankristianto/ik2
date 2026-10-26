#!/usr/bin/env bash
# Flag client or employer details in text meant for the public blog.
# Usage: leak-check.sh FILE...   Exit 1 when anything matches.
# Every list lives outside the repo so client names never get committed, one entry per line:
#   ~/.claude/blog-denylist.txt         client names and other banned words
#   ~/.claude/blog-leak-projects.txt    client project folders; their subfolder names are banned too
#   ~/.claude/blog-leak-stopwords.txt   words cut from folder names that are too common to ban
#   ~/.claude/blog-leak-patterns.txt    regexes for private hosts, orgs and paths
[ $# -gt 0 ] || { echo "usage: leak-check.sh FILE..." >&2; exit 2; }

conf="$HOME/.claude"
for f in blog-denylist.txt blog-leak-projects.txt blog-leak-stopwords.txt blog-leak-patterns.txt; do
  [ -f "$conf/$f" ] || echo "note: ~/.claude/$f is missing, that part of the check is skipped" >&2
done

terms=$(mktemp)
trap 'rm -f "$terms"' EXIT
generic=$(paste -sd '|' "$conf/blog-leak-stopwords.txt" 2>/dev/null)
generic=${generic:-^$}

while IFS= read -r dir; do
  [ -n "$dir" ] || continue
  for d in "$dir"/*/; do
    [ -d "$d" ] || continue
    name=$(basename "$d")
    echo "$name" | rg -iv "^($generic)$"
    echo "$name" | sed 's/\([a-z]\)\([A-Z]\)/\1 \2/g' | rg -o '[A-Za-z]{3,}' | rg -iv "^($generic)$"
  done
done < <(cat "$conf/blog-leak-projects.txt" 2>/dev/null) >> "$terms"
cat "$conf/blog-denylist.txt" >> "$terms" 2>/dev/null

status=0
report() { [ -n "$1" ] && { echo "$1"; status=1; }; }
for f in "$@"; do
  [ -s "$terms" ] && report "$(rg -n -i -w -f "$terms" "$f")"
  report "$(rg -n -P '\b(?!UTF|SHA|ISO|PHP|CVE|RFC|HTTP|MD|ES|X)[A-Z]{2,6}-\d{1,5}\b' "$f" | sed 's/$/   <- ticket key?/')"
  [ -s "$conf/blog-leak-patterns.txt" ] &&
    report "$(rg -n -i -f "$conf/blog-leak-patterns.txt" "$f" | sed 's/$/   <- private link or path?/')"
done
exit $status
