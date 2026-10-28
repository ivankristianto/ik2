# Secret scanning

[Betterleaks](https://github.com/betterleaks/betterleaks) checks commits for credential patterns (API tokens, keys, passwords in URLs) in two places: an opt-in `pre-push` hook on your machine, and the `Secrets (Betterleaks)` job in the Quality workflow. Both go through `scripts/secret-scan.sh`, which pins the binary and refuses to run in any setup that could quietly turn the scan off.

It only looks for credentials. It says nothing about whether client names, internal links, private notes or images are fine to publish. GitHub secret scanning and push protection stay on as a second layer.

## Pinned version

| | |
| :-- | :-- |
| Version | [v1.9.0](https://github.com/betterleaks/betterleaks/releases/tag/v1.9.0), released 2026-09-29, tag commit `81aff7a638638aae3a659845d089043e1d8fe9ac` |
| `darwin_arm64` tarball SHA-256 | `83dd7eaab13d44a1e8e347a17064cdef153a1478db473276d41b38d55613d3f2` |
| `linux_x64` tarball SHA-256 | `f8b185a39ffcece2a1ca82bf3a4e7435cd81963ffd16b7a9128daf75f35f6de7` |

Both hashes match the release's `checksums.txt` and passed `gh release verify-asset`. Use the [v1.9.0 docs](https://github.com/betterleaks/betterleaks/tree/v1.9.0/docs), not the default branch: `main` is v2, which changes the commands, config loading and ignore format.

Other platforms aren't supported. `install` stops with an error until a hash for that platform is added to the script.

## Set up a clone

From the host, not the tools container (the hook runs on the host):

```bash
scripts/secret-scan.sh install           # downloads into .tools/ (gitignored) and checks the SHA-256
git config core.hooksPath .githooks      # opt in to the pre-push hook
```

Cloning doesn't install the hook, and `core.hooksPath` replaces `.git/hooks` for that clone. Worktrees share the setting with their main clone, but `.tools/` lives in each checkout, so run `scripts/secret-scan.sh install` in every new worktree or its pushes are blocked.

## What gets scanned

The **pre-push hook** scans only the commits a push sends:

- updating a branch the remote has: `<remote tip>..<local tip>`
- a new branch, or a remote tip you haven't fetched: `<local tip> --not --remotes`, so commits already on any remote are skipped
- deleting a branch: nothing

Uncommitted and unstaged changes are never scanned. If the binary is missing or the wrong version, the push is blocked and the message gives the install command.

The **CI job** scans the event's commits:

| Event | Range | When the range can't be resolved |
| :-- | :-- | :-- |
| `pull_request` | `base.sha..head.sha` | fails, and an empty range also fails |
| `push` to `main` | `before..after` | scans the full history of `after` and says so in the job summary (new branch or force push) |
| `workflow_dispatch` | full history of `HEAD` | n/a |

The job checks out with `fetch-depth: 0` and fails on a shallow checkout. It runs the self-test (`tests/secret-scan.test.mjs`) before the real scan, has `contents: read` only, and has no release-commit skip. Push runs on `main` are never cancelled by a newer push (see the `concurrency` block in `quality.yml`), so no range is dropped.

The status check is `Secrets (Betterleaks)`. It isn't required yet. Making it required is a branch protection change, done separately.

## Exit codes

| Code | Meaning |
| :-- | :-- |
| 0 | clean |
| 1 | findings (values redacted) |
| 2 | setup or scan failure: missing or wrong binary, blocked config, bad revision, shallow checkout, empty PR range, scanner error |

A failure is never reported as clean.

## Flags and network

The wrapper always runs `betterleaks git --no-banner --no-color --verbose --redact --exit-code 2 --ignore-gitleaks-allow -i <repo> --log-opts="--text --diff-filter=tuxdb <range>"`.

- `--redact` replaces every matched value with `REDACTED` in all output. No report file is written, so nothing is uploaded as an artifact.
- `--text` is there because the binary's own `git log` call leaves it out, so a `-diff` entry in `.gitattributes` would otherwise hide a file's patch.
- Validation, which sends found values to provider APIs, is [off unless `--validation` is passed](https://github.com/betterleaks/betterleaks/blob/v1.9.0/cmd/root.go#L112). v1.9.0 has no environment variable that turns it on, and no analysis or revocation features. The wrapper never passes the flag, and a test fails if it ever does.
- I found no telemetry or update check in the v1.9.0 source. The only network call is the release download in `install`.

## Suppressions

Betterleaks v1.9.0 accepts several overrides without asking. The wrapper blocks them:

| Override | What the wrapper does |
| :-- | :-- |
| `.betterleaks.toml` or `.gitleaks.toml` in the repo | fails, so only the built-in rules apply |
| `BETTERLEAKS_CONFIG`, `GITLEAKS_CONFIG`, `BETTERLEAKS_CONFIG_TOML`, `GITLEAKS_CONFIG_TOML` | fails if any is set |
| `betterleaks:allow` / `gitleaks:allow` comments | ignored via `--ignore-gitleaks-allow` |
| `.gitleaksignore` | fails |
| `.betterleaksignore` | allowed, with rules below |

`.betterleaksignore` takes one commit-scoped fingerprint per line, with a `# reason` comment on the line above:

```
# Placeholder password in the dev install example, not a real credential
9021d81e772343630b89386a258b42aa51eae9ca:README.md:generic-password:101
```

The fingerprint is `<git.sha>:<path>:<rule id>:<line>`, all from the verbose finding. Entries without a commit (`file:rule:line`) are rejected because they would hide that line in every commit. The scan reads the file from the checked-out tree, so in CI a PR's own version applies. When a range touches `.betterleaksignore`, `.gitattributes`, the wrapper, the hook, the test or `quality.yml`, the job summary lists those files.

## If it finds something

- **Pre-push:** the commit hasn't left your machine. Remove the value, amend or rebase the unpushed commits, and push again.
- **CI:** the commit is already on GitHub, and this repo is public. Rotate the credential first. History rewrites are a separate decision.
- **False positive:** add the fingerprint to `.betterleaksignore` with a reason.

## Limits

- The hook is opt-in and `git push --no-verify` skips it.
- CI runs after the push, so it catches a leak after it is public, not before.
- Only commits in each event's range get scanned. History from before this gate, and branches pushed without a PR, are only covered by the full-history fallbacks.
- Content that exists only in a merge commit (a conflict resolution) isn't in `git log -p` output, so it isn't scanned.
- It finds what the built-in rules know. Anything else gets through.

## Run the tests

From the host, since `.tools/betterleaks` is a host binary:

```bash
node --test tests/secret-scan.test.mjs
```

The tests build throwaway repos in a temp dir and assemble fake tokens at runtime, so no complete token-shaped string is committed.

## Bump the version

1. Pick a stable (non-prerelease) tag from the [releases](https://github.com/betterleaks/betterleaks/releases) and read its changelog and the docs at that tag.
2. Download the two tarballs and `checksums.txt`, then check each one:
   ```bash
   gh release download vX.Y.Z -R betterleaks/betterleaks -p checksums.txt -p 'betterleaks_X.Y.Z_darwin_arm64.tar.gz' -p 'betterleaks_X.Y.Z_linux_x64.tar.gz'
   gh release verify-asset vX.Y.Z betterleaks_X.Y.Z_darwin_arm64.tar.gz -R betterleaks/betterleaks
   gh release verify-asset vX.Y.Z betterleaks_X.Y.Z_linux_x64.tar.gz -R betterleaks/betterleaks
   ```
3. Update `VERSION` and both `SHA256_*` lines in `scripts/secret-scan.sh`, and the table above.
4. Run `scripts/secret-scan.sh install` and the tests.

v2 changes the CLI, config loading and ignore format, so moving to it means rewriting the wrapper, not bumping a number.
