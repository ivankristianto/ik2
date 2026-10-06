# design-system/ — prototype kit notes

Loaded when working under `design-system/` (and relevant to the gitignored `samples/` sandbox). The token flow, design rules, and voice guide live in the root `CLAUDE.md`.

## Two parallel design-system kits

`design-system/ui_kits/blog/` and `samples/` are **separate, both real**, and **diverged**. They share component names (Header, Hero, ArticleCard, …) and token philosophy, but the files are not symlinked. When you change a component, decide which kit you're editing and check whether the change should propagate to the other.

| Kit                           | Stylesheet(s)                                                    | Status                               |
| :---------------------------- | :--------------------------------------------------------------- | :----------------------------------- |
| `design-system/ui_kits/blog/` | `kit.css` only                                                   | Canonical, in git                    |
| `samples/`                    | `assets/tokens.css` + `assets/kit.css` + `assets/extensions.css` | Sandbox, gitignored, has extra pages |

Neither is the production theme. The production theme is `wp-content/themes/ik2/`.

`design-system/ui_kits/blog/` reads tokens through `colors_and_type.css`, which aliases `tokens.css`. `pnpm tokens` generates `tokens.css` from `wp-content/themes/ik2/theme.json`, so change `theme.json` and regenerate; never edit `tokens.css` or `design-system/theme.json` by hand. `samples/assets/tokens.css` is an older hand-made copy and is not synced. The kits are light only; don't add dark-mode CSS.

## No build step

The `design-system/ui_kits/blog/` and `samples/` HTML prototypes run standalone via React + Babel UMD (`open design-system/ui_kits/blog/index.html`). They have no build step; JSX `<script type="text/babel">` order matters because components attach to globals — so when you add a new JSX file, register it in `index.html` in the right order.
