/**
 * API Rate Limiter - Admin scripts.
 *
 * @package Api_Rate_Limiter
 */
document.addEventListener( 'DOMContentLoaded', () => {

	// --- Tab navigation ---

	const tabs = document.querySelectorAll( '.nav-tab[data-tab]' );
	const panels = document.querySelectorAll( '.wptarl-tab-panel' );

	function activateTab( tabName ) {
		tabs.forEach( ( t ) => t.classList.remove( 'nav-tab-active' ) );
		const activeTab = document.querySelector( '[data-tab="' + tabName + '"]' );
		if ( activeTab ) {
			activeTab.classList.add( 'nav-tab-active' );
		}

		panels.forEach( ( panel ) => {
			panel.style.display = 'none';
		} );
		const activePanel = document.getElementById( tabName + '-panel' );
		if ( activePanel ) {
			activePanel.style.display = 'block';
		}
	}

	if ( tabs.length ) {
		const initialTab = window.location.hash.substring( 1 ) || 'settings';
		activateTab( initialTab );

		tabs.forEach( ( tab ) => {
			tab.addEventListener( 'click', ( e ) => {
				e.preventDefault();
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
		copyLink.addEventListener( 'click', ( e ) => {
			e.preventDefault();

			const ip = copyLink.dataset.ip || '';

			if ( ! ip ) {
				return;
			}

			const originalText = copyLink.textContent;

			navigator.clipboard.writeText( ip ).then( () => {
				copyLink.textContent = wptarlAdmin.copiedText;
				copyLink.classList.add( 'wptarl-copied' );
				setTimeout( () => {
					copyLink.textContent = originalText;
					copyLink.classList.remove( 'wptarl-copied' );
				}, 1500 );
			} );
		} );
	}
} );
