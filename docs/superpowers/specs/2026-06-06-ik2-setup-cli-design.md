# `wp ik2 setup` — site setup CLI command

## Goal

A modular WP-CLI command in the ik2 plugin that provisions the site to match
the theme's expectations: pages the templates hardcode links to, permalink
structure, timezone, and registration policy. Idempotent by default, with a
`--force` flag to re-apply page state. Output is a checklist with ✓/✗ icons.

## Command

`wp ik2 setup [--force]`, registered in `inc/cli/namespace.php` alongside
`ik2 stats`.

## Files

```
wp-content/plugins/ik2/inc/cli/
├── namespace.php                      # + require + add_command('ik2 setup', …)
├── class-setup-command.php            # Setup_Command: registry + runner + output
└── setup/
    ├── interface-setup-step.php       # Setup_Step interface
    ├── class-check-result.php         # Check_Result value object
    ├── class-pages-step.php           # create pages from manifest
    ├── class-permalinks-step.php      # /%postname%/ + flush rules
    ├── class-timezone-step.php        # Asia/Jakarta
    └── class-registration-step.php    # users_can_register = 0
```

## Contracts

- `Setup_Step::label(): string` — section heading (e.g. "Pages").
- `Setup_Step::run( bool $force ): array` — returns `Check_Result[]`.
- `Check_Result` — `label` (string), `success` (bool), `note` (string,
  e.g. "created", "exists, skipped", "already set", "updated").

`Setup_Command` holds an ordered array of step instances. Adding a step means
adding a class and one registry entry.

## Steps

1. **Pages** — static manifest derived from hardcoded links in the theme
   templates and parts (`/articles`, `/projects`, `/speaking`, `/about`,
   `/contact`, `/resume` in `parts/header.html` and page templates;
   `/privacy` in `parts/footer.html`):

   | slug     | title    |
   | -------- | -------- |
   | articles | Articles |
   | projects | Projects |
   | speaking | Speaking |
   | about    | About    |
   | contact  | Contact  |
   | resume   | Resume   |
   | privacy  | Privacy  |

   Per page, one check line:
   - Exists by slug (`get_page_by_path`, any post status) → skip, ✓
     "exists, skipped".
   - Missing → `wp_insert_post` (post_type `page`, status `publish`, empty
     content — block templates render the layout) → ✓ "created".
   - `--force` + exists → update title/status in place on the existing ID
     (ID stays stable so links keep working), restore from trash if
     trashed → ✓ "updated".

2. **Permalinks** — set `permalink_structure` to `/%postname%/` if
   different, then `flush_rewrite_rules()`. ✓ "already set" / "set".

3. **Timezone** — `timezone_string = Asia/Jakarta`, clear `gmt_offset`.
   Same already-set semantics.

4. **Registration** — `users_can_register = 0`.

`--force` only affects the Pages step; steps 2–4 are idempotent option
writes.

## Output

WP_CLI lines grouped by step label:

```
Pages
  ✓ articles — exists, skipped
  ✓ privacy — created
Permalinks
  ✓ /%postname%/ — already set
Timezone
  ✓ Asia/Jakarta — set
Registration
  ✓ users_can_register — already off
Setup complete: 10 ok, 0 failed.
```

## Error handling

Each step catches its own failures into
`Check_Result(success: false, note: <error message>)` — a ✗ line. Failures
do not abort the run; all steps complete, then the command exits non-zero
via `WP_CLI::error` if any check failed.

## Conventions

- Named methods / named callables only (project PHP convention).
- `IK2\Plugin\CLI` namespace, `declare(strict_types=1)`, WPCS style to pass
  `composer quality` (PHPCS + PHPStan level 6).

## Verification

- `composer dev:wp:cmd -- ik2 setup` on the dev stack (expect mostly
  "exists/already set" on current data), then `--force`.
- `composer quality`.
