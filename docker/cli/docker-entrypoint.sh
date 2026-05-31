#!/bin/sh
set -eu

# The cli image inherits `VOLUME /var/www/html` from wordpress:cli. At runtime
# that path is an anonymous volume, and Compose can preserve a prior container's
# volume across redeploys — so a stale volume may shadow this build's code. To
# keep the cli "fresh per deploy", the canonical webroot is baked at the
# non-volume path /usr/src/html and mirrored into /var/www/html on every start.
#
# The image seeds an 82:82-owned wp-content skeleton, so this runs as www-data
# and writes into www-data-owned dirs. Only code directories are mirrored;
# wp-content/uploads is a separately-mounted volume and must never be touched.
src=/usr/src/html
dst=/var/www/html

if [ -d "$src" ]; then
	mkdir -p "$dst/wp-content"

	for dir in wp-admin wp-includes wp-content/themes wp-content/plugins wp-content/mu-plugins vendor; do
		if [ -d "$src/$dir" ]; then
			rm -rf "${dst:?}/$dir"
			mkdir -p "$dst/$(dirname "$dir")"
			cp -a "$src/$dir" "$dst/$(dirname "$dir")/"
		fi
	done

	# Top-level core files + baked wp-config.php (env-driven).
	for file in "$src"/*.php; do
		[ -e "$file" ] && cp -a "$file" "$dst/"
	done
fi

# Hand off to the upstream wordpress:cli entrypoint (prepends `wp` for wp-cli
# subcommands, otherwise execs the command verbatim).
exec docker-entrypoint.sh "$@"
