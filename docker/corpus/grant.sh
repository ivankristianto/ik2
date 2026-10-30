#!/usr/bin/env bash
# Applies grant.sql against the running local db service as root.
set -euo pipefail

cd "$(dirname "$0")/../.."

sql_file=docker/corpus/grant.sql

# Credentials and database name come from the db container's own environment,
# so they match whatever docker-compose.yml and .env resolved.
db_sql() {
	docker compose exec -T db sh -c \
		'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb -uroot -N -B "$MARIADB_DATABASE"'
}

if [ -z "$(docker compose ps --status running -q db)" ]; then
	echo "grant.sh: the db service is not running. Start the stack with 'composer dev' first." >&2
	exit 1
fi

# The table list is read from grant.sql so the two can't drift apart.
tables=$(grep -o 'wp_ik2_corpus_[a-z_]*' "$sql_file" | sort -u)
in_list=$(printf '%s\n' "$tables" | sed "s/.*/'&'/" | paste -sd, -)
existing=$(echo "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN (${in_list});" | db_sql)

missing=''
while read -r table; do
	if ! printf '%s\n' "$existing" | grep -qx "$table"; then
		missing="$missing $table"
	fi
done <<< "$tables"

if [ -n "$missing" ]; then
	echo "grant.sh: corpus tables missing:$missing" >&2
	echo "The ik2 plugin creates them on its next load (any page view or wp-cli command). Run this again after that." >&2
	exit 1
fi

db_sql < "$sql_file"
echo "SHOW GRANTS FOR 'ik2_corpus'@'%';" | db_sql
