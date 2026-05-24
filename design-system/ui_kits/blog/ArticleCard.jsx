// ArticleCard.jsx — standard post card with cover, title, meta, tags.

function ArticleCard( { post, onOpen = () => {} } ) {
	return (
		<article className="ik-article-card" onClick={ () => onOpen( post ) }>
			<a
				className="ik-article-card__cover"
				href="#"
				onClick={ ( e ) => {
					e.preventDefault();
					onOpen( post );
				} }
				aria-hidden="true"
			>
				<div
					className="ik-article-card__cover-fill"
					style={ { background: post.cover.bg } }
				>
					<span>{ post.cover.label }</span>
				</div>
			</a>
			<div className="ik-article-card__meta">
				<span>{ post.date }</span>
				<span aria-hidden="true">·</span>
				<span>{ post.readingTime }</span>
			</div>
			<h3 className="ik-article-card__title">
				<a
					href="#"
					onClick={ ( e ) => {
						e.preventDefault();
						onOpen( post );
					} }
				>
					{ post.title }
				</a>
			</h3>
			<p className="ik-article-card__excerpt">{ post.excerpt }</p>
			<TagList tags={ post.tags } />
		</article>
	);
}

window.ArticleCard = ArticleCard;
