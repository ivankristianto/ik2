// Run in a logged-in Chrome tab on a post or its preview (?p=ID&preview=true) with the
// javascript_tool; save the returned string as facts.json for `audit.py --facts`.
// Same shape as audit.py's Extractor. URLs lose their query strings because the
// browser tool refuses output that carries them.
( () => {
	const bare = ( url ) => {
		try {
			const u = new URL( url, location.href );
			return u.origin + u.pathname + u.hash;
		} catch {
			return url;
		}
	};
	const clean = ( text ) =>
		( text || '' )
			.replace( /https?:\/\/\S+/g, ( m ) => bare( m ) )
			.replace( /\s+/g, ' ' )
			.trim();
	// Text the reader sees, minus theme chrome: citation labels and hidden source previews.
	const visibleText = ( el ) => {
		const copy = el.cloneNode( true );
		copy.querySelectorAll( '.ik-cite__label, [popover]' ).forEach( ( n ) =>
			n.remove()
		);
		return clean( copy.textContent );
	};
	const inChrome = ( el ) => el.closest( '[popover], .ik-cite__label' );

	const meta = {};
	document
		.querySelectorAll( 'meta[name], meta[property]' )
		.forEach( ( m ) => {
			const key = (
				m.getAttribute( 'name' ) || m.getAttribute( 'property' )
			).toLowerCase();
			const value = m.getAttribute( 'content' ) || '';
			meta[ key ] = /^https?:/.test( value ) ? bare( value ) : clean( value );
		} );

	// Reduce JSON-LD to the article node so no search-action URL template leaks through.
	const jsonld = [ ...document.querySelectorAll( 'script[type="application/ld+json"]' ) ].map(
		( s ) => {
			try {
				const data = JSON.parse( s.textContent );
				const nodes = ( Array.isArray( data ) ? data : [ data ] ).flatMap(
					( d ) => d[ '@graph' ] || [ d ]
				);
				const article = nodes.find( ( n ) =>
					[].concat( n[ '@type' ] ).some( ( t ) =>
						[ 'Article', 'BlogPosting', 'TechArticle', 'NewsArticle' ].includes( t )
					)
				);
				return JSON.stringify(
					article
						? {
								'@type': article[ '@type' ],
								headline: clean( article.headline ),
								datePublished: article.datePublished || '',
								author: article.author ? true : '',
								image: article.image ? true : '',
						  }
						: {}
				);
			} catch ( e ) {
				return '{ invalid JSON-LD: ' + e.message + ' }';
			}
		}
	);

	const main = document.querySelector( 'main' );
	const headings = [ ...document.querySelectorAll( 'h1, h2, h3, h4, h5, h6' ) ]
		.filter( ( h ) => ! inChrome( h ) && ( h.tagName === 'H1' || main?.contains( h ) ) )
		.map( ( h ) => [ Number( h.tagName[ 1 ] ), visibleText( h ), true ] );

	const body = document.querySelector( '.ik-article__body' );
	const pick = ( selector ) =>
		body ? [ ...body.querySelectorAll( selector ) ].filter( ( el ) => ! inChrome( el ) ) : [];
	const featured = document.querySelector( '.ik-article__featured img' );

	return JSON.stringify( {
		source: bare( location.href ),
		lang: document.documentElement.lang || null,
		titles: [ ...document.head.querySelectorAll( 'title' ) ].map( ( t ) => clean( t.textContent ) ),
		meta,
		canonical: document.querySelector( 'link[rel="canonical"]' )?.href
			? bare( document.querySelector( 'link[rel="canonical"]' ).href )
			: null,
		jsonld,
		headings,
		body: {
			paragraphs: pick( 'p' ).map( visibleText ),
			text: body ? visibleText( body ) : '',
			images: pick( 'img' ).map( ( img ) => ( {
				src: bare( img.currentSrc || img.src ),
				alt: img.hasAttribute( 'alt' ) ? img.getAttribute( 'alt' ) : null,
				decorative:
					img.getAttribute( 'role' ) === 'presentation' ||
					img.getAttribute( 'aria-hidden' ) === 'true',
			} ) ),
			links: pick( 'a' ).map( ( a ) => ( {
				href: ( ( raw ) => ( ! raw || raw.startsWith( '#' ) ? raw : bare( a.href ) ) )(
					a.getAttribute( 'href' )
				),
				// An image's alt is the accessible name of the link around it.
				text: visibleText( a ) || clean( a.querySelector( 'img[alt]' )?.alt ),
				role: a.getAttribute( 'role' ) || '',
				label: a.getAttribute( 'aria-label' ) || '',
				rel: a.getAttribute( 'rel' ) || '',
			} ) ),
		},
		featured_alt: featured ? featured.getAttribute( 'alt' ) : null,
	} );
} )();
