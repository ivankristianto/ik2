// Front-end JS entry. Styles are no longer bundled here — critical CSS is
// inlined in <head>, block styles load on demand, and page-section styles are
// enqueued per template (see inc/assets.php). This entry ships only the
// command palette behaviour, loaded deferred in the footer.
import './palette.js';
