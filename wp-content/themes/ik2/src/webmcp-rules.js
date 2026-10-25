/**
 * Input checks, REST URLs and response shaping for the WebMCP article tools.
 * Pure, so node:test covers it without a DOM; webmcp.js does the fetching.
 */

// Mirror the pills in blocks/articles-filters/render.php. Both are categories.
export const TOPICS = [
	'all',
	'wordpress',
	'ai',
	'performance',
	'security',
	'web-apis',
	'tooling',
];
export const FORMATS = [ 'all', 'guide', 'note', 'experiment' ];

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

// The category slugs a list request filters on; "all" means no filter.
export function categorySlugs( { topic, format } ) {
	return [ topic, format ].filter( ( slug ) => slug !== 'all' );
}

export function categoriesUrl( restRoot, slugs ) {
	const search = new URLSearchParams( {
		slug: slugs.join( ',' ),
		_fields: 'id,slug',
	} );
	return `${ restRoot }wp/v2/categories?${ search }`;
}

// Topic and format both live in `category`, so the IDs must all match, not any.
export function listUrl( restRoot, { categoryIds, limit, page } ) {
	const params = { per_page: limit, page, _fields: SUMMARY_FIELDS };
	if ( categoryIds.length ) {
		params[ 'categories[terms]' ] = categoryIds.join( ',' );
		params[ 'categories[operator]' ] = 'AND';
	}
	return postsUrl( restRoot, params );
}

// Null for another origin's URL, so an agent can't point the tool at a third-party site.
export function articleSlug( ref, origin ) {
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
		path = url.pathname;
	}
	const slug = path.split( '/' ).filter( Boolean ).pop() ?? '';
	return /^[a-z0-9-]+$/i.test( slug ) ? slug.toLowerCase() : null;
}

export function articleUrl( restRoot, slug ) {
	return postsUrl( restRoot, { slug, _fields: ARTICLE_FIELDS } );
}

// Anonymous REST still lists password-protected posts, with the body withheld.
export function isProtected( post ) {
	return Boolean( post?.excerpt?.protected || post?.content?.protected );
}

export function toSummary( post, toText ) {
	return {
		title: toText( post.title?.rendered ?? '' ),
		url: post.link,
		date: post.date,
		summary: toText( post.excerpt?.rendered ?? '' ),
	};
}

export function toArticle( post, toText ) {
	return {
		title: toText( post.title?.rendered ?? '' ),
		url: post.link,
		date: post.date,
		modified: post.modified,
		content: toText( post.content?.rendered ?? '' ),
	};
}

export function toResults( posts, total, page, toText ) {
	return {
		total,
		page,
		articles: posts
			.filter( ( post ) => ! isProtected( post ) )
			.map( ( post ) => toSummary( post, toText ) ),
	};
}
