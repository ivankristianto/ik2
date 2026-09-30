#!/usr/bin/env bash
# Pull production content into the local dev stack. Arguments pass straight to sync.php.
set -euo pipefail

root="$(git -C "$(dirname "$0")" rev-parse --show-toplevel)"
cd "$root"

if [ -f .env ]; then
	set -a
	# shellcheck disable=SC1091
	. ./.env
	set +a
fi

# Fall back to the application password the ik2 MCP server already uses (.mcp.json is gitignored).
if [ -z "${IK2_PROD_USER:-}" ] && [ -f .mcp.json ]; then
	IK2_PROD_USER="$(node -p "require('./.mcp.json').mcpServers?.ik2?.env?.WP_API_USERNAME ?? ''")"
	IK2_PROD_APP_PASSWORD="$(node -p "require('./.mcp.json').mcpServers?.ik2?.env?.WP_API_PASSWORD ?? ''")"
fi

: "${IK2_PROD_USER:?Set IK2_PROD_USER in .env or the environment}"
: "${IK2_PROD_APP_PASSWORD:?Set IK2_PROD_APP_PASSWORD in .env or the environment}"
export IK2_PROD_URL="${IK2_PROD_URL:-https://www.ivankristianto.com}"
export IK2_PROD_USER IK2_PROD_APP_PASSWORD

# -e NAME (no value) forwards from this shell, so the password stays out of the process list.
exec docker compose exec -T \
	-e IK2_PROD_URL -e IK2_PROD_USER -e IK2_PROD_APP_PASSWORD \
	wp-cli wp eval-file - "$@" < .claude/skills/sync-from-prod/scripts/sync.php
