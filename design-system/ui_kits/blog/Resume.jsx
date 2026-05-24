// Resume.jsx — resume / about page.

function Resume( { data } ) {
	return (
		<section className="ik-resume container-narrow">
			<header className="ik-resume__header">
				<div className="ik-resume__eyebrow">RESUME · UPDATED 2026</div>
				<h1 className="ik-resume__name">{ data.name }</h1>
				<div className="ik-resume__title">{ data.title }</div>
				<div className="ik-resume__location">{ data.location }</div>
				<p className="ik-resume__summary">{ data.summary }</p>
			</header>

			<section className="ik-resume__section">
				<h2>Experience</h2>
				<div className="ik-resume__exp">
					{ data.experience.map( ( e, i ) => (
						<div className="ik-resume__exp-row" key={ i }>
							<div className="ik-resume__exp-when">
								{ e.from }–{ e.to }
							</div>
							<div className="ik-resume__exp-body">
								<div className="ik-resume__exp-role">
									{ e.role } · <span>{ e.org }</span>
								</div>
								<div className="ik-resume__exp-note">
									{ e.note }
								</div>
							</div>
						</div>
					) ) }
				</div>
			</section>

			<section className="ik-resume__section">
				<h2>Skills</h2>
				<div className="ik-resume__skills">
					{ data.skills.map( ( s, i ) => (
						<div className="ik-resume__skill" key={ i }>
							<div className="ik-resume__skill-group">
								{ s.group }
							</div>
							<ul>
								{ s.items.map( ( x, j ) => (
									<li key={ j }>{ x }</li>
								) ) }
							</ul>
						</div>
					) ) }
				</div>
			</section>

			<section className="ik-resume__section">
				<h2>Contact</h2>
				<div className="ik-resume__contact">
					<a href="#">hi@ivankristianto.com</a>
					<a href="#">github.com/ivankristianto</a>
					<a href="#">linkedin.com/in/ivankristianto</a>
				</div>
			</section>
		</section>
	);
}

window.Resume = Resume;
