// Hero.jsx — homepage hero. Mono eyebrow, big title, blurb, CTA stack.

function Hero({ data, onNavigate = () => {} }) {
  return (
    <section className="ik-hero container-full">
      <div className="ik-hero__eyebrow">{data.eyebrow}</div>
      <h1 className="ik-hero__title">{data.title}</h1>
      <p className="ik-hero__blurb">{data.blurb}</p>
      <div className="ik-hero__cta">
        <a className="btn btn--primary" href="#" onClick={(e) => { e.preventDefault(); onNavigate("guides"); }}>
          Browse Guides
        </a>
        <a className="btn btn--secondary" href="#" onClick={(e) => { e.preventDefault(); onNavigate("resume"); }}>
          Read Resume
        </a>
        <a className="ik-hero__textlink" href="#" onClick={(e) => { e.preventDefault(); onNavigate("writing"); }}>
          Latest Articles <span aria-hidden="true">→</span>
        </a>
      </div>
    </section>
  );
}

window.Hero = Hero;
