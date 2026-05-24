// TagPill.jsx — soft blue chip used inline anywhere.

function TagPill( { children, onClick } ) {
	return (
		<span
			className="ik-tag"
			onClick={ onClick }
			role={ onClick ? 'button' : undefined }
			tabIndex={ onClick ? 0 : -1 }
		>
			{ children }
		</span>
	);
}

function TagList( { tags = [], onTagClick } ) {
	return (
		<div className="ik-tag-list">
			{ tags.map( ( t ) => (
				<TagPill
					key={ t }
					onClick={ onTagClick ? () => onTagClick( t ) : undefined }
				>
					{ t }
				</TagPill>
			) ) }
		</div>
	);
}

Object.assign( window, { TagPill, TagList } );
