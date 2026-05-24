// app.jsx — composes the blog UI kit into a click-thru prototype.

function HomePage( { onNavigate, onOpenPost } ) {
	const M = window.MOCK;
	return (
		<main>
			<Hero data={ M.hero } onNavigate={ onNavigate } />

			{ /* Latest writing */ }
			<section
				className="ik-section container-full"
				data-screen-label="Home / Latest writing"
			>
				<div className="ik-section__head">
					<h2 className="ik-section__title">Latest writing</h2>
					<a
						className="ik-section__more"
						href="#"
						onClick={ ( e ) => {
							e.preventDefault();
							onNavigate( 'writing' );
						} }
					>
						All writing →
					</a>
				</div>
				<div className="ik-grid-3">
					{ M.posts.slice( 0, 3 ).map( ( p ) => (
						<ArticleCard
							key={ p.slug }
							post={ p }
							onOpen={ onOpenPost }
						/>
					) ) }
				</div>
			</section>

			{ /* Guides */ }
			<section
				className="ik-section ik-section--muted"
				data-screen-label="Home / Guides"
			>
				<div className="container-full">
					<div className="ik-section__head">
						<h2 className="ik-section__title">Guides</h2>
						<a
							className="ik-section__more"
							href="#"
							onClick={ ( e ) => {
								e.preventDefault();
								onNavigate( 'guides' );
							} }
						>
							All guides →
						</a>
					</div>
					<div className="ik-grid-2">
						{ M.guides.slice( 0, 4 ).map( ( g ) => (
							<GuideCard key={ g.slug } guide={ g } />
						) ) }
					</div>
				</div>
			</section>

			{ /* Notes (changelog) */ }
			<section
				className="ik-section container-full"
				data-screen-label="Home / Notes"
			>
				<div className="ik-section__head">
					<h2 className="ik-section__title">Notes</h2>
					<a
						className="ik-section__more"
						href="#"
						onClick={ ( e ) => {
							e.preventDefault();
							onNavigate( 'notes' );
						} }
					>
						All notes →
					</a>
				</div>
				<div>
					{ M.notes.slice( 0, 4 ).map( ( n ) => (
						<NoteRow key={ n.date } note={ n } />
					) ) }
				</div>
			</section>
		</main>
	);
}

function WritingPage( { onOpenPost } ) {
	const M = window.MOCK;
	return (
		<main
			className="container-full"
			style={ { paddingBlock: 'var(--space-7) var(--space-8)' } }
			data-screen-label="Writing index"
		>
			<header style={ { marginBottom: 'var(--space-7)' } }>
				<div className="ik-resume__eyebrow">WRITING · 122 POSTS</div>
				<h1
					style={ {
						fontSize: 'clamp(2rem, 5vw, 2.75rem)',
						letterSpacing: '-0.04em',
						margin: 'var(--space-3) 0 var(--space-4)',
					} }
				>
					Everything I've written
				</h1>
				<p
					style={ {
						maxWidth: 'var(--width-content)',
						color: 'var(--color-text-muted)',
						fontSize: '1.125rem',
						lineHeight: 1.7,
						margin: 0,
					} }
				>
					WordPress, performance, security, AI, and the boring devops
					in between. Newest first.
				</p>
			</header>
			<div className="ik-grid-3">
				{ M.posts.map( ( p ) => (
					<ArticleCard
						key={ p.slug }
						post={ p }
						onOpen={ onOpenPost }
					/>
				) ) }
				{ M.posts.map( ( p ) => (
					<ArticleCard
						key={ p.slug + '-2' }
						post={ p }
						onOpen={ onOpenPost }
					/>
				) ) }
			</div>
		</main>
	);
}

function GuidesPage() {
	const M = window.MOCK;
	return (
		<main
			className="container-full"
			style={ { paddingBlock: 'var(--space-7) var(--space-8)' } }
			data-screen-label="Guides index"
		>
			<header style={ { marginBottom: 'var(--space-7)' } }>
				<div className="ik-resume__eyebrow">
					GUIDES · MULTI-PART SERIES
				</div>
				<h1
					style={ {
						fontSize: 'clamp(2rem, 5vw, 2.75rem)',
						letterSpacing: '-0.04em',
						margin: 'var(--space-3) 0 var(--space-4)',
					} }
				>
					Guides
				</h1>
				<p
					style={ {
						maxWidth: 'var(--width-content)',
						color: 'var(--color-text-muted)',
						fontSize: '1.125rem',
						lineHeight: 1.7,
						margin: 0,
					} }
				>
					Longer pieces I keep updated. Pick one, read it end to end,
					and you'll come out the other side with something working.
				</p>
			</header>
			<div className="ik-grid-2">
				{ M.guides.map( ( g ) => (
					<GuideCard key={ g.slug } guide={ g } />
				) ) }
			</div>
		</main>
	);
}

function NotesPage() {
	const M = window.MOCK;
	return (
		<main
			className="container-full"
			style={ { paddingBlock: 'var(--space-7) var(--space-8)' } }
			data-screen-label="Notes index"
		>
			<header style={ { marginBottom: 'var(--space-7)' } }>
				<div className="ik-resume__eyebrow">NOTES · CHRONOLOGICAL</div>
				<h1
					style={ {
						fontSize: 'clamp(2rem, 5vw, 2.75rem)',
						letterSpacing: '-0.04em',
						margin: 'var(--space-3) 0 var(--space-4)',
					} }
				>
					Notes
				</h1>
				<p
					style={ {
						maxWidth: 'var(--width-content)',
						color: 'var(--color-text-muted)',
						fontSize: '1.125rem',
						lineHeight: 1.7,
						margin: 0,
					} }
				>
					Half-formed thoughts and small things I want to remember.
					Lower stakes than an article.
				</p>
			</header>
			<div style={ { maxWidth: 'var(--width-content)' } }>
				{ M.notes.map( ( n ) => (
					<NoteRow key={ n.date } note={ n } />
				) ) }
				{ M.notes.map( ( n ) => (
					<NoteRow
						key={ n.date + '-2' }
						note={ {
							...n,
							date: n.date.replace( '2026', '2025' ),
						} }
					/>
				) ) }
			</div>
		</main>
	);
}

function App() {
	const [ route, setRoute ] = React.useState( 'home' );
	const M = window.MOCK;

	const onOpenPost = () => setRoute( 'article' );

	let screen;
	if ( route === 'home' )
		screen = <HomePage onNavigate={ setRoute } onOpenPost={ onOpenPost } />;
	else if ( route === 'writing' )
		screen = <WritingPage onOpenPost={ onOpenPost } />;
	else if ( route === 'guides' ) screen = <GuidesPage />;
	else if ( route === 'notes' ) screen = <NotesPage />;
	else if ( route === 'article' )
		screen = (
			<Article
				article={ M.article }
				onBack={ () => setRoute( 'writing' ) }
			/>
		);
	else if ( route === 'resume' ) screen = <Resume data={ M.resume } />;

	return (
		<React.Fragment>
			<Header
				current={ route === 'article' ? 'writing' : route }
				onNavigate={ setRoute }
			/>
			{ screen }
			<Footer />
		</React.Fragment>
	);
}

const root = ReactDOM.createRoot( document.getElementById( 'root' ) );
root.render( <App /> );
