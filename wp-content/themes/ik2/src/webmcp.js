/**
 * Read-only WebMCP tools over the public articles, for browser AI agents.
 * Imported by index.js only where `document.modelContext` exists.
 */

import {
	CATEGORY_SLUGS,
	FORMATS,
	MAX_LIMIT,
	DEFAULT_LIMIT,
	TOPICS,
	articleUrl,
	categoriesUrl,
	categoryIdsFor,
	isProtected,
	listUrl,
	readArticleRef,
	readListInput,
	readSearchInput,
	searchUrl,
	toArticle,
	toResults,
} from './webmcp-rules.js';

const restRoot =
	document.querySelector( 'link[rel="https://api.w.org/"]' )?.href ??
	`${ window.location.origin }/wp-json/`;

const READ_ONLY = { readOnlyHint: true };

const PAGING_PROPERTIES = {
	limit: {
		type: 'integer',
		minimum: 1,
		maximum: MAX_LIMIT,
		default: DEFAULT_LIMIT,
		description: 'How many articles to return.',
	},
	page: {
		type: 'integer',
		minimum: 1,
		default: 1,
		description: 'Page of results, counting from 1.',
	},
};

let categoryIdsBySlug = null;

function htmlToText( html ) {
	const doc = new window.DOMParser().parseFromString( html, 'text/html' );
	doc.querySelectorAll( 'script, style' ).forEach( ( el ) => el.remove() );
	doc.querySelectorAll( 'li' ).forEach( ( li ) => {
		li.prepend( '- ' );
		li.append( '\n' );
	} );
	return [ ...doc.body.childNodes ]
		.map( ( node ) => node.textContent.trim() )
		.filter( Boolean )
		.join( '\n\n' );
}

// Cookies are omitted so a logged-in visitor can never get drafts or private posts back.
async function getJson( url, signal ) {
	const res = await fetch( url, { credentials: 'omit', signal } );
	const data = await res.json().catch( ( err ) => {
		if ( signal?.aborted ) {
			throw err;
		}
		return null;
	} );
	if ( ! res.ok ) {
		return {
			error:
				data?.message ?? `The site answered with HTTP ${ res.status }.`,
		};
	}
	return { data, total: Number( res.headers.get( 'X-WP-Total' ) ?? 0 ) };
}

// Shared by concurrent calls, so it takes no signal: one cancelled call shouldn't fail another.
function resolveCategories() {
	if ( ! categoryIdsBySlug ) {
		categoryIdsBySlug = getJson(
			categoriesUrl( restRoot, CATEGORY_SLUGS )
		).then( ( res ) => {
			if ( res.error ) {
				throw new Error( res.error );
			}
			return new Map(
				res.data.map( ( term ) => [ term.slug, term.id ] )
			);
		} );
		// A failed lookup must not stick for the rest of the visit.
		categoryIdsBySlug.catch( () => {
			categoryIdsBySlug = null;
		} );
	}
	return categoryIdsBySlug;
}

// Lets one call stop waiting on a shared promise without cancelling it for the others.
function untilAborted( promise, signal ) {
	return new Promise( ( resolve, reject ) => {
		signal.throwIfAborted();
		const onAbort = () => reject( signal.reason );
		signal.addEventListener( 'abort', onAbort, { once: true } );
		promise
			.then( resolve, reject )
			.finally( () => signal.removeEventListener( 'abort', onAbort ) );
	} );
}

// The agent never sees a thrown error's message, so only a cancellation may throw.
async function withAgentErrors( signal, work ) {
	try {
		return await work();
	} catch ( err ) {
		if ( signal.aborted ) {
			throw err;
		}
		return { error: err instanceof Error ? err.message : String( err ) };
	}
}

async function fetchResults( url, page, signal ) {
	const res = await getJson( url, signal );
	return res.error ? res : toResults( res.data, res.total, page, htmlToText );
}

async function searchArticles( input, { signal } ) {
	const args = readSearchInput( input );
	if ( args.error ) {
		return args;
	}
	return withAgentErrors( signal, () =>
		fetchResults( searchUrl( restRoot, args ), args.page, signal )
	);
}

async function listArticles( input, { signal } ) {
	const args = readListInput( input );
	if ( args.error ) {
		return args;
	}
	return withAgentErrors( signal, async () => {
		const idsBySlug = await untilAborted( resolveCategories(), signal );
		const categoryIds = categoryIdsFor( args, idsBySlug );
		if ( ! categoryIds ) {
			return toResults( [], 0, args.page, htmlToText );
		}
		const url = listUrl( restRoot, { ...args, categoryIds } );
		return fetchResults( url, args.page, signal );
	} );
}

async function getArticle( input, { signal } ) {
	const ref = readArticleRef( input?.article, window.location.origin );
	if ( ! ref ) {
		return {
			error: `Pass the URL of an article on ${ window.location.origin }, or its slug.`,
		};
	}
	return withAgentErrors( signal, async () => {
		const res = await getJson( articleUrl( restRoot, ref ), signal );
		if ( res.error ) {
			return res;
		}
		const post = res.data[ 0 ];
		if ( ! post ) {
			return {
				error: `No published article matches "${ input.article }".`,
			};
		}
		if ( isProtected( post ) ) {
			return { error: 'That article is password protected.' };
		}
		return toArticle( post, htmlToText );
	} );
}

const TOOLS = [
	{
		name: 'search_articles',
		description:
			"Searches Ivan Kristianto's published articles by keyword and returns titles, links, dates and summaries. Use when the reader wants to know whether Ivan has written about something.",
		inputSchema: {
			type: 'object',
			properties: {
				query: {
					type: 'string',
					description:
						'Words to look for in article titles and text.',
				},
				...PAGING_PROPERTIES,
			},
			required: [ 'query' ],
		},
		execute: searchArticles,
		annotations: READ_ONLY,
	},
	{
		name: 'list_articles',
		description:
			"Lists Ivan Kristianto's published articles, newest first, optionally narrowed to a topic and a format. Use to browse recent writing or everything on one subject.",
		inputSchema: {
			type: 'object',
			properties: {
				topic: {
					type: 'string',
					enum: TOPICS,
					default: 'all',
					description: 'Subject area to narrow the list to.',
				},
				format: {
					type: 'string',
					enum: FORMATS,
					default: 'all',
					description: 'Article format to narrow the list to.',
				},
				...PAGING_PROPERTIES,
			},
		},
		execute: listArticles,
		annotations: READ_ONLY,
	},
	{
		name: 'get_article',
		description:
			'Returns the full text of one published article on this site. Use after finding an article to read, quote or summarise it.',
		inputSchema: {
			type: 'object',
			properties: {
				article: {
					type: 'string',
					description:
						"The article's URL on this site, or its slug (the last part of the URL path).",
				},
			},
			required: [ 'article' ],
		},
		execute: getArticle,
		annotations: READ_ONLY,
	},
];

// One tool failing to register shouldn't take the others with it.
for ( const tool of TOOLS ) {
	Promise.resolve( document.modelContext.registerTool( tool ) ).catch(
		() => {}
	);
}
