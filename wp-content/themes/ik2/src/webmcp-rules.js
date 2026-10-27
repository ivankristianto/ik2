/**
 * Input checks, REST URLs, response shaping and cancellation for the WebMCP tools.
 * Pure, so node:test covers it without a DOM; webmcp.js does the fetching.
 */

// Mirror the pills in blocks/articles-filters/render.php. Both are categories.
export const TOPICS = [
	'all',
	'wordpress',
	'ai',
	'performance',
	'web-apis',
	'tooling',
	'misc',
];
export const FORMATS = [ 'all', 'guide', 'note', 'experiment' ];
export const CATEGORY_SLUGS = [ ...TOPICS, ...FORMATS ].filter(
	( slug ) => slug !== 'all'
);

export const DEFAULT_LIMIT = 10;
export const MAX_LIMIT = 20;

const SUMMARY_FIELDS = 'id,date,link,title,excerpt';
const ARTICLE_FIELDS = 'id,date,modified,link,title,content';

function postsUrl( restRoot, params ) {
	const search = new URLSearchParams( {
		status: 'publish',
		...params,
	} );
	return `${ restRoot }wp/v2/posts?${ search }`;
}

function readPaging( input ) {
	const limit = input.limit ?? DEFAULT_LIMIT;
	const page = input.page ?? 1;
	if ( ! Number.isInteger( limit ) || limit < 1 || limit > MAX_LIMIT ) {
		return {
			error: `limit must be a whole number from 1 to ${ MAX_LIMIT }.`,
		};
	}
	if ( ! Number.isInteger( page ) || page < 1 ) {
		return { error: 'page must be a whole number from 1.' };
	}
	return { limit, page };
}

// `input ?? {}`: a throw here would reach the agent as a bare failure, not a message.
export function readSearchInput( input ) {
	const args = input ?? {};
	const query = typeof args.query === 'string' ? args.query.trim() : '';
	if ( ! query ) {
		return { error: 'Pass the words to search for in query.' };
	}
	const paging = readPaging( args );
	return paging.error ? paging : { query, ...paging };
}

export function readListInput( input ) {
	const args = input ?? {};
	const topic = args.topic ?? 'all';
	const format = args.format ?? 'all';
	if ( ! TOPICS.includes( topic ) ) {
		return { error: `Unknown topic "${ topic }".`, topics: TOPICS };
	}
	if ( ! FORMATS.includes( format ) ) {
		return { error: `Unknown format "${ format }".`, formats: FORMATS };
	}
	const paging = readPaging( args );
	return paging.error ? paging : { topic, format, ...paging };
}

export function searchUrl( restRoot, { query, limit, page } ) {
	return postsUrl( restRoot, {
		search: query,
		per_page: limit,
		page,
		_fields: SUMMARY_FIELDS,
	} );
}

// Null when a slug has no category yet: that filter matches no articles, not every article.
export function categoryIdsFor( { topic, format }, idsBySlug ) {
	const slugs = [ topic, format ].filter( ( slug ) => slug !== 'all' );
	if ( slugs.some( ( slug ) => ! idsBySlug.has( slug ) ) ) {
		return null;
	}
	return slugs.map( ( slug ) => idsBySlug.get( slug ) );
}

export function categoriesUrl( restRoot, slugs ) {
	const search = new URLSearchParams( {
		slug: slugs.join( ',' ),
		_fields: 'id,slug',
	} );
	return `${ restRoot }wp/v2/categories?${ search }`;
}

// Topic and format both live in `category`, so the IDs must all match, not any.
// Unlike the archive this skips child categories: REST can't AND two clauses on one taxonomy.
export function listUrl( restRoot, { categoryIds, limit, page } ) {
	const params = { per_page: limit, page, _fields: SUMMARY_FIELDS };
	if ( categoryIds.length ) {
		params[ 'categories[terms]' ] = categoryIds.join( ',' );
		params[ 'categories[operator]' ] = 'AND';
	}
	return postsUrl( restRoot, params );
}

// Null for any other origin or path shape, so the tool can't be aimed at other pages.
export function readArticleRef( ref, origin ) {
	const value = typeof ref === 'string' ? ref.trim() : '';
	if ( ! value ) {
		return null;
	}
	let path = value;
	if ( /^https?:\/\//i.test( value ) ) {
		let url;
		try {
			url = new URL( value );
		} catch {
			return null;
		}
		if ( url.origin !== origin ) {
			return null;
		}
		const id = url.searchParams.get( 'p' );
		if ( id && /^\d+$/.test( id ) ) {
			return { id: Number( id ) };
		}
		path = url.pathname;
	}
	// Permalinks are /%postname%/, plus a page number on posts split with <!--nextpage-->.
	const segments = path.split( '/' ).filter( Boolean );
	const isPaged = segments.length === 2 && /^\d+$/.test( segments[ 1 ] );
	if ( segments.length !== 1 && ! isPaged ) {
		return null;
	}
	let slug;
	try {
		slug = decodeURIComponent( segments[ 0 ] ).toLowerCase();
	} catch {
		return null;
	}
	return /^[\p{L}\p{N}_-]+$/u.test( slug ) ? { slug } : null;
}

export function articleUrl( restRoot, { slug, id } ) {
	const match = id ? { include: id } : { slug };
	return postsUrl( restRoot, { ...match, _fields: ARTICLE_FIELDS } );
}

// Anonymous REST still lists password-protected posts, with the body withheld.
export function isProtected( post ) {
	return Boolean( post?.excerpt?.protected || post?.content?.protected );
}

function toHeading( post, toText ) {
	return {
		title: toText( post.title?.rendered ?? '' ),
		url: post.link,
		date: post.date,
	};
}

function toSummary( post, toText ) {
	return {
		...toHeading( post, toText ),
		summary: toText( post.excerpt?.rendered ?? '' ),
	};
}

export function toArticle( post, toText ) {
	return {
		...toHeading( post, toText ),
		modified: post.modified,
		content: toText( post.content?.rendered ?? '' ),
	};
}

// `total` is WordPress's count, as on the archive. Anonymous search already skips protected posts.
export function toResults( posts, total, page, toText ) {
	return {
		total,
		page,
		articles: posts
			.filter( ( post ) => ! isProtected( post ) )
			.map( ( post ) => toSummary( post, toText ) ),
	};
}

// Lets one call stop waiting on a shared promise without cancelling it for the others.
export function untilAborted( promise, signal ) {
	return new Promise( ( resolve, reject ) => {
		signal.throwIfAborted();
		const onAbort = () => reject( signal.reason );
		signal.addEventListener( 'abort', onAbort, { once: true } );
		promise
			.then( resolve, reject )
			.finally( () => signal.removeEventListener( 'abort', onAbort ) );
	} );
}
