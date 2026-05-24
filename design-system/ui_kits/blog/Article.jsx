// Article.jsx — single-post layout. Renders a block list from mockData.

function CodeBlock( { children } ) {
	const [ copied, setCopied ] = React.useState( false );
	const onCopy = () => {
		try {
			navigator.clipboard && navigator.clipboard.writeText( children );
			setCopied( true );
			setTimeout( () => setCopied( false ), 1400 );
		} catch ( e ) {}
	};
	return (
		<div className="ik-codeblock">
			<button className="ik-codeblock__copy" onClick={ onCopy }>
				{ copied ? 'Copied' : 'Copy' }
			</button>
			<pre>
				<code>{ children }</code>
			</pre>
		</div>
	);
}

function ArticleBody( { blocks } ) {
	return (
		<div className="ik-article-body">
			{ blocks.map( ( b, i ) => {
				if ( b.kind === 'h2' ) return <h2 key={ i }>{ b.text }</h2>;
				if ( b.kind === 'h3' ) return <h3 key={ i }>{ b.text }</h3>;
				if ( b.kind === 'p' ) return <p key={ i }>{ b.text }</p>;
				if ( b.kind === 'ol' )
					return (
						<ol key={ i }>
							{ b.items.map( ( x, j ) => (
								<li key={ j }>{ x }</li>
							) ) }
						</ol>
					);
				if ( b.kind === 'ul' )
					return (
						<ul key={ i }>
							{ b.items.map( ( x, j ) => (
								<li key={ j }>{ x }</li>
							) ) }
						</ul>
					);
				if ( b.kind === 'pre' )
					return <CodeBlock key={ i }>{ b.text }</CodeBlock>;
				if ( b.kind === 'callout' )
					return (
						<Callout
							key={ i }
							variant={ b.variant }
							title={ b.title }
						>
							{ b.text }
						</Callout>
					);
				return null;
			} ) }
		</div>
	);
}

function Article( { article, onBack = () => {} } ) {
	return (
		<article className="ik-article container-narrow">
			<div className="ik-article__crumbs">
				<a
					href="#"
					onClick={ ( e ) => {
						e.preventDefault();
						onBack();
					} }
				>
					← All writing
				</a>
			</div>
			<h1 className="ik-article__title">{ article.title }</h1>
			<div className="ik-article__meta">
				<span>{ article.date }</span>
				<span aria-hidden="true">·</span>
				<span>{ article.readingTime }</span>
				<span aria-hidden="true">·</span>
				<TagList tags={ article.tags } />
			</div>
			<p className="ik-article__lead">{ article.intro }</p>
			<ArticleBody blocks={ article.body } />
			<hr className="ik-article__sep" />
			<div className="ik-article__share">
				<span>Share:</span>
				<a href="#">Twitter</a>
				<a href="#">LinkedIn</a>
				<a href="#">Copy link</a>
			</div>
		</article>
	);
}

Object.assign( window, { Article, ArticleBody, CodeBlock } );
