#!/usr/bin/env bash
# Print the opening prompts of recent Claude Code sessions, newest first.
# Usage: sessions.sh [days=14] [prompts_per_session=5]
# Raw output names clients. Read it, never paste it into a public place.
days=${1:-14}
per=${2:-5}
cd "$HOME/.claude/projects" || exit 1

find . -mindepth 2 -maxdepth 2 -name '*.jsonl' -mtime "-$days" -print0 |
  while IFS= read -r -d '' f; do
    printf '%s\t%s\n' "$(stat -f '%Sm' -t %F "$f")" "$f"
  done | sort -r |
  while IFS=$'\t' read -r day f; do
    echo "## $day $(basename "$(dirname "$f")") $(basename "$f" .jsonl | cut -c1-8)"
    jq -r 'select(.type=="user" and (.message.content|type=="string")) | .message.content' "$f" 2>/dev/null |
      rg -v '^\s*(<|\[|/)' | awk 'length>25' | head -n "$per" | cut -c1-300
  done
