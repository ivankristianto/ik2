// Footer.jsx — soft paper footer with social links + RSS.

function Footer() {
  return (
    <footer className="ik-footer">
      <div className="container-full ik-footer__inner">
        <div className="ik-footer__brand">
          <div className="ik-footer__name">Ivan Kristianto<span>.</span></div>
          <div className="ik-footer__tagline">Passionately share and learn.</div>
        </div>
        <nav className="ik-footer__nav" aria-label="Social">
          <a href="#" aria-label="GitHub">
            <img src="../../assets/icons/github.svg" alt="" width="20" height="20"/><span>GitHub</span>
          </a>
          <a href="#" aria-label="LinkedIn">
            <img src="../../assets/icons/linkedin.svg" alt="" width="20" height="20"/><span>LinkedIn</span>
          </a>
          <a href="#" aria-label="Twitter / X">
            <img src="../../assets/icons/twitter.svg" alt="" width="20" height="20"/><span>Twitter</span>
          </a>
          <a href="#" aria-label="WordPress.org">
            <img src="../../assets/icons/wordpress.svg" alt="" width="20" height="20"/><span>WordPress.org</span>
          </a>
          <a href="#" className="ik-footer__rss" aria-label="RSS feed">
            <img src="../../assets/icons/rss.svg" alt="" width="20" height="20"/><span>RSS</span>
          </a>
        </nav>
      </div>
      <div className="container-full ik-footer__bottom">
        <span>© 2026 · Built on WordPress · Theme based on the Ink, Paper, Signal design system.</span>
        <span>Last published 2026-04-12</span>
      </div>
    </footer>
  );
}

window.Footer = Footer;
