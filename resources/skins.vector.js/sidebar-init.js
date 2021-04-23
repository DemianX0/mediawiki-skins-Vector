/** @type {boolean} */
var mwSidebarOpenState; // eslint-disable-line no-unused-vars
var mwSidebarPanelState; // eslint-disable-line no-unused-vars

( function initSidebarState() {
	var
		/** @type {HTMLElement} */ checkbox,
		/** @type {boolean} */ savedState;
	try {
		checkbox = document.getElementById( 'mw-sidebar-checkbox' );
		if ( !( checkbox instanceof HTMLInputElement ) ) {
			return;
		}
		// Set global var before using localStorage, which might throw an exception.
		window.mwSidebarOpenState = checkbox.checked;

		savedState = localStorage.getItem( 'mw-sidebar-open' );
		if ( savedState ) {
			checkbox.checked = savedState !== '0';
			window.mwSidebarOpenState = checkbox.checked;
		}

		savedPanel = localStorage.getItem( 'mw-sidebar-panel' );
		checkbox = savedPanel && document.getElementById( 'mw-sidebar-' + savedPanel + '-radio' );
		if ( checkbox ) {
			checkbox.checked = true;
			window.mwSidebarPanelState = savedPanel;
		}
	} catch ( e ) {
		// mw.log() is not loaded at this point.
		/* eslint-disable no-console */
		if ( console.log ) {
			console.log( 'Failed to load sidebar state:', e );
		}
	}
}() );
