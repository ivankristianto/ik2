# Betterleaks for secret scanning, through one wrapper

Commits are checked for credentials by Betterleaks v1.9.0, run through `scripts/secret-scan.sh` from an opt-in `pre-push` hook and the `Secrets (Betterleaks)` CI job. Gitleaks was the obvious candidate, but its maintainer now directs feature work to Betterleaks, which reads the same config format. The wrapper exists because v1.9.0 honours repo config files, ignore files, allow comments and config environment variables without asking. Any of those could switch the gate off without a visible failure, so the wrapper blocks them and allows only commit-scoped `.betterleaksignore` entries with a reason.

## Considered Options

- **Gitleaks.** Mature and well known, but new development has moved to Betterleaks.
- **A pre-commit hook.** Catches a leak earliest, but runs on every commit, and slow commits get bypassed. A pre-push hook still runs before anything leaves the machine and runs once per push.
- **Full-history scan on every CI run.** Simple coverage, but the range scan plus a full-history fallback for force pushes and manual runs covers the same commits on this repo.
- **A third-party Action.** There is no official one. Downloading the release and checking a committed SHA-256 needs no extra code to trust.

## Consequences

Moving to Betterleaks v2 means rewriting the wrapper, because v2 changes the commands, config loading and ignore format. If GitHub push protection one day covers every pattern Betterleaks knows, the hook could go, but the CI job should stay.
