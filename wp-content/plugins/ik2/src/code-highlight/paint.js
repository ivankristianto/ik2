/**
 * Colors code blocks with the CSS Custom Highlight API. The DOM is never
 * touched, which is what lets the same code run on the editor's RichText.
 */
import Prism from './prism';

const HIGHLIGHTS = {
	comment: 'ik2-code-comment',
	keyword: 'ik2-code-keyword',
	string: 'ik2-code-string',
};

// `null` paints a token as plain text even inside a colored parent (the `<`
// and `=` inside an HTML tag), `undefined` (unlisted) inherits the parent.
const TOKEN_ROLES = {
	comment: 'comment',
	prolog: 'comment',
	doctype: 'comment',
	cdata: 'comment',
	shebang: 'comment',
	keyword: 'keyword',
	tag: 'keyword',
	selector: 'keyword',
	atrule: 'keyword',
	important: 'keyword',
	builtin: 'keyword',
	directive: 'keyword',
	deleted: 'keyword',
	string: 'string',
	char: 'string',
	'attr-value': 'string',
	regex: 'string',
	url: 'string',
	number: 'string',
	boolean: 'string',
	constant: 'string',
	inserted: 'string',
	punctuation: null,
	operator: null,
	'attr-name': null,
	interpolation: null,
};

export function isSupported() {
	return typeof CSS !== 'undefined' && 'highlights' in CSS;
}

function roleOf( token ) {
	for ( const name of [ token.type ].concat( token.alias || [] ) ) {
		if ( name in TOKEN_ROLES ) {
			return TOKEN_ROLES[ name ];
		}
	}
	return undefined;
}

function collectSpans( stream, start, inherited, spans ) {
	let offset = start;
	for ( const token of stream ) {
		if ( typeof token === 'string' ) {
			if ( inherited && token.length ) {
				spans.push( [ offset, offset + token.length, inherited ] );
			}
		} else {
			const own = roleOf( token );
			const role = own === undefined ? inherited : own;
			collectSpans( [].concat( token.content ), offset, role, spans );
		}
		offset += token.length;
	}
	return spans;
}

// RichText renders line breaks as <br>, so each one counts as a "\n" that
// belongs to no text node; without it a `#` comment would run to the end.
function textNodesOf( element ) {
	const walker = element.ownerDocument.createTreeWalker(
		element,
		NodeFilter.SHOW_TEXT | NodeFilter.SHOW_ELEMENT
	);
	const nodes = [];
	let text = '';
	for ( let node = walker.nextNode(); node; node = walker.nextNode() ) {
		if ( node.nodeType === Node.TEXT_NODE ) {
			nodes.push( { node, start: text.length } );
			text += node.data;
		} else if ( node.nodeName === 'BR' ) {
			text += '\n';
		}
	}
	return { nodes, text };
}

// A start offset on a boundary or a <br> belongs to the next text node, an
// end offset to the previous one, so no range spans an empty edge.
function locate( nodes, offset, isEnd ) {
	let lo = 0;
	let hi = nodes.length - 1;
	while ( lo < hi ) {
		const mid = ( lo + hi + 1 ) >> 1;
		const start = nodes[ mid ].start;
		if ( isEnd ? start < offset : start <= offset ) {
			lo = mid;
		} else {
			hi = mid - 1;
		}
	}
	const { node, start } = nodes[ lo ];
	const inNode = offset - start;
	if ( ! isEnd && inNode > node.length && nodes[ lo + 1 ] ) {
		return [ nodes[ lo + 1 ].node, 0 ];
	}
	return [ node, Math.max( 0, Math.min( inNode, node.length ) ) ];
}

function rangesFor( pre, grammar, buckets ) {
	const { nodes, text } = textNodesOf( pre );
	if ( ! nodes.length ) {
		return;
	}
	const spans = collectSpans( Prism.tokenize( text, grammar ), 0, null, [] );
	for ( const [ from, to, role ] of spans ) {
		const range = new Range();
		range.setStart( ...locate( nodes, from, false ) );
		range.setEnd( ...locate( nodes, to, true ) );
		buckets[ role ].push( range );
	}
}

/**
 * Repaint every `pre.wp-block-code[data-language]` under `root`.
 * @param {ParentNode} root Where to look for code blocks.
 */
export function paint( root ) {
	if ( ! isSupported() ) {
		return;
	}
	const buckets = { comment: [], keyword: [], string: [] };
	for ( const pre of root.querySelectorAll(
		'pre.wp-block-code[data-language]'
	) ) {
		const grammar = Prism.languages[ pre.dataset.language ];
		if ( grammar ) {
			rangesFor( pre, grammar, buckets );
		}
	}
	for ( const [ role, ranges ] of Object.entries( buckets ) ) {
		CSS.highlights.set( HIGHLIGHTS[ role ], new Highlight( ...ranges ) );
	}
}
