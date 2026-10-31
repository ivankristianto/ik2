/**
 * Tools → Corpus: Queue all Articles, one batch of 50 per request so no request hits a timeout.
 */
( function () {
	const button = document.getElementById( 'ik2-corpus-queue-all' );

	if ( ! button ) {
		return;
	}

	const progress = document.getElementById( 'ik2-corpus-queue-all-progress' );
	const status = document.getElementById( 'ik2-corpus-queue-all-status' );

	async function queueAll() {
		const totals = {
			queued: 0,
			unchanged: 0,
			removed: 0,
			skipped: 0,
			failed: 0,
		};
		let after = 0;
		let checked = 0;
		let done = false;

		button.disabled = true;
		progress.hidden = false;
		progress.value = 0;

		try {
			while ( ! done ) {
				const batch = await window.wp.apiFetch( {
					path: '/ik2/v1/corpus/queue-all',
					method: 'POST',
					data: { after },
				} );

				Object.keys( totals ).forEach( ( key ) => {
					totals[ key ] += batch.counts[ key ] || 0;
				} );
				checked += batch.processed;
				after = batch.after;
				done = batch.done;
				progress.max = Math.max( batch.total, 1 );
				progress.value = Math.min( checked, batch.total );
				status.textContent = `${ checked } of ${ batch.total } Articles checked`;
			}

			status.textContent =
				`Done: ${ totals.queued } queued, ${ totals.unchanged } unchanged, ${ totals.removed } marked for removal, ${ totals.failed } failed.` +
				( totals.queued + totals.removed > 0
					? ' The next Run picks them up.'
					: '' );
		} catch ( error ) {
			// Batches already sent stay queued, so running it again carries on safely.
			status.textContent = `Stopped after ${ checked } Articles: ${ error.message }. Run it again to finish.`;
		} finally {
			button.disabled = false;
		}
	}

	button.addEventListener( 'click', queueAll );
} )();
