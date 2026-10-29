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

resolve() {
	git rev-parse --verify --quiet "$1^{commit}"
}

check_ignore_file() {
	[ -e "$1" ] || return 0
	n=0
	reason=0
	bad=0
	while IFS= read -r line || [ -n "$line" ]; do
		n=$((n + 1))
		case "$line" in
			*[![:space:]]*) ;;
			*) continue ;;
		esac
		case "$line" in
			'#'*) reason=1 && continue ;;
		esac
		# Three-part file:rule:line entries match that line in every commit, so only commit-scoped ones are allowed.
		if ! printf '%s\n' "$line" | grep -Eq '^[0-9a-f]{40}:[^:]+:[A-Za-z0-9._-]+:[0-9]+$'; then
			echo "  line $n: expected <40-hex commit>:<file>:<rule>:<line>" >&2
			bad=1
		elif [ "$reason" = 0 ]; then
			echo "  line $n: add a '# reason' comment on the line above" >&2
			bad=1
		fi
		reason=0
	done <"$1"
	[ "$bad" = 0 ] || fail ".betterleaksignore rejected, see docs/secret-scanning.md"
}

check_setup() {
	[ -x "$BIN" ] || fail "betterleaks is not installed. Run: scripts/secret-scan.sh install"
	got=$("$BIN" version 2>/dev/null) || got=unknown
	[ "$got" = "$VERSION" ] || fail "found betterleaks $got at $BIN, expected $VERSION. Run: scripts/secret-scan.sh install"
	for var in BETTERLEAKS_CONFIG GITLEAKS_CONFIG BETTERLEAKS_CONFIG_TOML GITLEAKS_CONFIG_TOML; do
		eval "val=\${$var:-}"
		[ -z "$val" ] || fail "$var is set, unset it so the built-in rules apply"
	done
	REPO=$(git rev-parse --show-toplevel 2>/dev/null) || fail "not inside a git work tree"
	# Betterleaks loads these from the scan target without asking, so any of them could quietly switch the gate off.
	for f in .betterleaks.toml .gitleaks.toml .gitleaksignore; do
		[ ! -e "$REPO/$f" ] || fail "$REPO/$f would override the built-in rules, remove it (see docs/secret-scanning.md)"
	done
	check_ignore_file "$REPO/.betterleaksignore"
}

# $1 is git log revisions, e.g. "a..b" or "b --not --remotes".
scan() {
	# The binary's git log omits --text, so a "-diff" .gitattributes entry would hide a file's patch.
	status=0
	"$BIN" git --no-banner --no-color --verbose --redact --exit-code 2 --ignore-gitleaks-allow \
		-i "$REPO" --log-opts="--text --diff-filter=tuxdb $1" "$REPO" </dev/null || status=$?
	case $status in
		0) ;;
		2)
			echo "secret-scan: credentials found in the commits above (values redacted)." >&2
			echo "secret-scan: remove them, or for a false positive see docs/secret-scanning.md." >&2
			exit 1
			;;
		*) fail "betterleaks exited $status, the scan did not complete" ;;
	esac
}

pre_push() {
	check_setup
	while read -r local_ref local_sha remote_ref remote_sha; do
		[ "$local_sha" != "$ZERO_SHA" ] || continue
		if [ "$remote_sha" != "$ZERO_SHA" ] && resolve "$remote_sha" >/dev/null; then
			range="$remote_sha..$local_sha"
		else
			range="$local_sha --not --remotes"
		fi
		echo "secret-scan: $local_ref -> $remote_ref ($range)"
		scan "$range"
	done
}

report() {
	echo "secret-scan: $1"
	if [ -n "${GITHUB_STEP_SUMMARY:-}" ]; then
		printf '%s\n\n' "$1" >>"$GITHUB_STEP_SUMMARY"
	fi
}

# Names any commit in the range that edits what decides the gate's result, so review catches it.
report_guarded_changes() {
	changed=$(git log --format= --name-only "$1" -- .betterleaksignore .gitattributes \
		scripts/secret-scan.sh .githooks tests/secret-scan.test.mjs .github/workflows/quality.yml | sort -u)
	[ -n "$changed" ] || return 0
	report "This range changes files that control the secret scan: $(echo "$changed" | tr '\n' ' ')"
}

ci() {
	check_setup
	[ "$(git rev-parse --is-shallow-repository)" = false ] || fail "shallow checkout, use fetch-depth: 0"
	case "${1:-}" in
		pull_request)
			base=$(resolve "${2:-}") || fail "pull request base '${2:-}' is not in this checkout"
			head=$(resolve "${3:-}") || fail "pull request head '${3:-}' is not in this checkout"
			count=$(git rev-list --count "$base..$head")
			[ "$count" -gt 0 ] || fail "$base..$head holds no commits, refusing to report a clean scan"
			report "Scanning $count pull request commit(s): $base..$head"
			report_guarded_changes "$base..$head"
			scan "$base..$head"
			;;
		push)
			head=$(resolve "${3:-}") || fail "pushed commit '${3:-}' is not in this checkout"
			if [ "${2:-$ZERO_SHA}" != "$ZERO_SHA" ] && base=$(resolve "$2"); then
				report "Scanning $(git rev-list --count "$base..$head") pushed commit(s): $base..$head"
				report_guarded_changes "$base..$head"
				scan "$base..$head"
			else
				report "Previous tip '${2:-}' is not in this checkout (new branch or force push), scanning the full history of $head"
				scan "$head"
			fi
			;;
		workflow_dispatch)
			head=$(resolve HEAD) || fail "HEAD does not resolve"
			report "Manual run, scanning the full history of $head"
			scan "$head"
			;;
		*) fail "unsupported event '${1:-}'" ;;
	esac
}

case "${1:-}" in
	install) install ;;
	pre-push) pre_push ;;
	ci)
		shift
		ci "$@"
		;;
	*) fail "usage: $0 install | pre-push <remote> <url> | ci <event> [from] [to]" ;;
esac
