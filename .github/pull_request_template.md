## Summary

<!-- What does this change do, and why? Two or three sentences. Link the issue if there is one. -->

Closes #

## Type of change

- [ ] Feature
- [ ] Bug fix
- [ ] Refactor (no behavior change)
- [ ] Dependency or infrastructure update
- [ ] Docs or content
- [ ] Design system / tokens

## Changes

<!-- The notable changes, grouped by area (theme, plugin, docker, ci, deps...). Tables are welcome for version bumps. -->

-

## How to test

<!-- Steps a reviewer can follow on the local Docker stack. Include URLs, wp-cli commands, and what they should see. -->

1. `composer dev`
2.

## Screenshots

<!-- Before / after for any visual change. Desktop and mobile widths. Delete this section if nothing visual changed. -->

| Before | After |
|---|---|
|  |  |

## Risk and rollback

<!-- What could break in production, and how to undo it. Mention DB migrations, cache flushes, image rebuilds, or env vars that need setting in Dokploy. Write "Low: revert the merge" if that is the whole story. -->

## Checklist

- [ ] `composer quality` passes (PHPCS + PHPStan)
- [ ] `pnpm lint` passes (ESLint + Stylelint)
- [ ] `pnpm build` succeeds and `node --test 'tests/*.test.mjs'` passes
- [ ] Docker images build (`docker build --target production .`) if `Dockerfile`, `composer.json`, or `package.json` changed
- [ ] No hardcoded colors or spacing; new tokens added to both `theme.json` copies and `colors_and_type.css`
- [ ] No `!important`, no new shadows or gradients, focus-visible styles intact
- [ ] Commits are atomic and follow Conventional Commits
