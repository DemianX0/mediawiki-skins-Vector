/** @type {boolean} */
var mwSidebarInitialState; // eslint-disable-line no-unused-vars

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
		window.mwSidebarInitialState = checkbox.checked;

		savedState = localStorage.getItem( 'vector-sidebar-open' );
		if ( !savedState ) {
			return;
		}
		checkbox.checked = savedState !== '0';
		window.mwSidebarInitialState = checkbox.checked;
	} catch ( e ) {
		// mw.log() is not loaded at this point.
		/* eslint-disable no-console */
		if ( console.log ) {
			console.log( 'Failed to load sidebar state:', e );
		}
	}
}() );
