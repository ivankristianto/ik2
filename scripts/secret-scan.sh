#!/bin/sh
# Betterleaks credential scan for pushes and CI. See docs/secret-scanning.md.
#
#   secret-scan.sh install                    fetch the pinned binary into .tools/
#   secret-scan.sh pre-push <remote> <url>    scan the commits a push sends (refs on stdin)
#   secret-scan.sh ci <event> [from] [to]     scan a pull request, push or dispatch range
#
# Exit codes: 0 clean, 1 findings, 2 setup or scan failure.

set -eu

VERSION=1.9.0
SHA256_darwin_arm64=83dd7eaab13d44a1e8e347a17064cdef153a1478db473276d41b38d55613d3f2
SHA256_linux_x64=f8b185a39ffcece2a1ca82bf3a4e7435cd81963ffd16b7a9128daf75f35f6de7

ZERO_SHA=0000000000000000000000000000000000000000
SCRIPT_DIR=$(CDPATH='' cd -- "$(dirname -- "$0")" && pwd)
TOOLS_DIR=$(dirname -- "$SCRIPT_DIR")/.tools
BIN=$TOOLS_DIR/betterleaks

fail() {
	printf 'secret-scan: %s\n' "$*" >&2
	exit 2
}

platform() {
	case "$(uname -s)/$(uname -m)" in
		Darwin/arm64) echo darwin_arm64 ;;
		Linux/x86_64) echo linux_x64 ;;
		*) fail "no pinned checksum for $(uname -s)/$(uname -m); add one to $0" ;;
	esac
}

sha256() {
	if command -v sha256sum >/dev/null 2>&1; then
		sha256sum "$1" | cut -d' ' -f1
	else
		shasum -a 256 "$1" | cut -d' ' -f1
	fi
}

install() {
	if [ -x "$BIN" ] && [ "$("$BIN" version 2>/dev/null)" = "$VERSION" ]; then
		echo "secret-scan: betterleaks $VERSION already installed at $BIN"
		return
	fi
	plat=$(platform)
	eval "expected=\$SHA256_$plat"
	asset="betterleaks_${VERSION}_${plat}.tar.gz"
	tmp=$(mktemp -d)
	trap 'rm -rf "$tmp"' EXIT
	curl -fsSL --proto '=https' -o "$tmp/$asset" \
		"https://github.com/betterleaks/betterleaks/releases/download/v$VERSION/$asset" ||
		fail "download of $asset failed"
	actual=$(sha256 "$tmp/$asset")
	[ "$actual" = "$expected" ] || fail "checksum mismatch for $asset: got $actual, expected $expected"
	tar -xzf "$tmp/$asset" -C "$tmp" betterleaks || fail "could not extract $asset"
	mkdir -p "$TOOLS_DIR"
	mv "$tmp/betterleaks" "$BIN"
	chmod 755 "$BIN"
	echo "secret-scan: installed betterleaks $VERSION at $BIN"
}

case "${1:-}" in
	install) install ;;
	*) fail "usage: $0 install | pre-push <remote> <url> | ci <event> [from] [to]" ;;
esac
