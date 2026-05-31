#!/bin/sh
set -eu

# The cli image inherits `VOLUME /var/www/html` from wordpress:cli. At runtime
# that path is an anonymous volume, and the uploads volume is mounted at
# /var/www/html/wp-content/uploads — Docker auto-creates the wp-content parent
# as root, and a stale volume can shadow this build's code. To keep the cli
# "fresh per deploy" and writable, the canonical webroot is baked at the
# non-volume path /usr/src/html and mirrored into /var/www/html on every start
# (as root), then we drop to www-data to run wp.
#
# Only code directories are mirrored; wp-content/uploads is a separately-mounted
# volume and must never be touched here.
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

	# Ensure the refreshed webroot is owned by www-data. wp-content may have been
	# created as root by the uploads sub-mount; the copied trees keep their 82:82
	# ownership from the source. The uploads sub-mount is left untouched.
	chown 82:82 "$dst" "$dst/wp-content"
	chown 82:82 "$dst"/*.php 2>/dev/null || true
fi

# Drop to www-data and hand off to the upstream wordpress:cli entrypoint
# (prepends `wp` for wp-cli subcommands, otherwise execs the command verbatim).
exec su-exec www-data docker-entrypoint.sh "$@"
