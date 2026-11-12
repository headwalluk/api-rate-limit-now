/**
 * API Rate Limiter - Admin scripts.
 *
 * @package Api_Rate_Limiter
 */
document.addEventListener( 'DOMContentLoaded', () => {

	// --- Tab navigation ---

	const tabs = document.querySelectorAll( '.nav-tab[data-tab]' );
	const panels = document.querySelectorAll( '.wptarl-tab-panel' );

	function activateTab( requestedTabName ) {
		// An unknown hash shows the Settings tab rather than an empty page.
		const tabName = document.getElementById( requestedTabName + '-panel' ) ? requestedTabName : 'settings';

		for ( const tab of tabs ) {
			tab.classList.toggle( 'nav-tab-active', tab.dataset.tab === tabName );
		}

		for ( const panel of panels ) {
			panel.hidden = panel.id !== tabName + '-panel';
		}
	}

	if ( tabs.length ) {
		const initialTab = window.location.hash.substring( 1 ) || 'settings';
		activateTab( initialTab );

		tabs.forEach( ( tab ) => {
			tab.addEventListener( 'click', ( clickEvent ) => {
				clickEvent.preventDefault();
				const tabName = tab.dataset.tab;
				window.location.hash = tabName;
				activateTab( tabName );
			} );
		} );

		window.addEventListener( 'hashchange', () => {
			const tabName = window.location.hash.substring( 1 ) || 'settings';
			activateTab( tabName );
		} );
	}

	// --- Click to copy IP ---

	const copyLink = document.querySelector( '.wptarl-copy-ip' );

	if ( copyLink ) {
		copyLink.addEventListener( 'click', ( clickEvent ) => {
			clickEvent.preventDefault();

			const ip = copyLink.dataset.ip || '';

			if ( ip && navigator.clipboard ) {
				const originalText = copyLink.textContent;

				navigator.clipboard.writeText( ip ).then( () => {
					copyLink.textContent = wptarlAdmin.copiedText;
					copyLink.classList.add( 'wptarl-copied' );
					setTimeout( () => {
						copyLink.textContent = originalText;
						copyLink.classList.remove( 'wptarl-copied' );
					}, 1500 );
				} ).catch( ( clipboardError ) => {
					console.error( 'API Rate Limiter: could not copy the IP address', clipboardError );
				} );
			}
		} );
	}
} );
