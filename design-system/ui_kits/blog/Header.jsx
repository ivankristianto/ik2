// Header.jsx — wordmark, primary nav, Resume button.
// Use: <Header current="home" onNavigate={slug => ...}/>

const NAV = [
	{ slug: 'home', label: 'Home' },
	{ slug: 'guides', label: 'Guides' },
	{ slug: 'writing', label: 'Writing' },
	{ slug: 'notes', label: 'Notes' },
];

function Header( { current = 'home', onNavigate = () => {} } ) {
	return (
		<header className="ik-header container-full">
			<a
				className="ik-header__brand"
				href="#"
				onClick={ ( e ) => {
					e.preventDefault();
					onNavigate( 'home' );
				} }
			>
				<span>Ivan Kristianto</span>
				<span className="ik-header__dot">.</span>
			</a>
			<nav className="ik-header__nav" aria-label="Primary">
				{ NAV.map( ( item ) => (
					<a
						key={ item.slug }
						href="#"
						className={
							'ik-header__link' +
							( current === item.slug ? ' is-current' : '' )
						}
						onClick={ ( e ) => {
							e.preventDefault();
							onNavigate( item.slug );
						} }
					>
						{ item.label }
					</a>
				) ) }
				<a
					href="#"
					className={
						'ik-header__resume' +
						( current === 'resume' ? ' is-current' : '' )
					}
					onClick={ ( e ) => {
						e.preventDefault();
						onNavigate( 'resume' );
					} }
				>
					Resume
				</a>
			</nav>
		</header>
	);
}

window.Header = Header;
