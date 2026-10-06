// Must be imported before prism-core: Prism reads `manual` at load time, and
// without it highlightAll() would rewrite any `code[class*="language-"]` it finds.
if ( ! globalThis.Prism ) {
	globalThis.Prism = { manual: true };
}
