# Own image lightbox instead of core's "Expand on click"

Readers can open an Article's images in a lightbox and step through every image in that Article, in document order. Core's `core/image` lightbox (WP 7.1) only steps between images inside one block-based `core/gallery`, and it skips the old `<ul>` galleries still in the archive. Bending it to cover a whole Article would mean faking a shared gallery id through `render_block` filters and writing to core's private Interactivity store, which changes between releases. So the theme ships its own small `<dialog>`-based lightbox and leaves core's lightbox off (`settings.blocks["core/image"].lightbox` stays unset).

## Considered Options

- **Core lightbox, per image.** No code to write, but no prev/next across the Article.
- **Core lightbox, faked gallery context.** Gets navigation, but couples us to undocumented core internals.
- **Own module (chosen).** About one small script and stylesheet. It can also decide at runtime which images are worth enlarging, which core can't.

## Consequences

If core later supports navigation across a whole post, revisit this. Don't enable core's lightbox alongside ours, or clicks will open two overlays.
