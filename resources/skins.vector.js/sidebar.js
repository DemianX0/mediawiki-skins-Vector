/**
 * JavaScript enhancement to the collapsible sidebar.
 *
 * The sidebar provides basic show/hide functionality with CSS
 * but JavaScript is used for progressive enhancements.
 *
 * Enhancements include:
 * - Update `aria-role`s based on expanded/collapsed state.
 * - Update button icon based on expanded/collapsed state.
 * - Persist the sidebar state for logged-in users.
 *
 */

/** @interface MwApi */
/** @interface MwApiConstructor */
/** @interface CheckboxHack */

/** @type {CheckboxHack} */
var CheckboxHack = require( /** @type {string} */ ( 'mediawiki.page.ready' ) ).CheckboxHack;
var SIDEBAR_BUTTON_ID = 'mw-sidebar-button',
	SIDEBAR_CHECKBOX_ID = 'mw-sidebar-checkbox',
	SIDEBAR_PREFERENCE_NAME = 'VectorSidebarVisible';
var debounce = require( /** @type {string} */ ( 'mediawiki.util' ) ).debounce;
/** @type {CheckboxHack} */ var checkboxHack;
/** @type {MwApi} */ var api;

/**
 * Execute a debounced API request to save the sidebar user preference.
 * The request is meant to fire 1000 milliseconds after the last click on
 * the sidebar button. Use debounce().
 *
 * @this CheckboxHack
 * @return {void}
 */
function saveSidebarPreference() {
	api = api || new mw.Api();
	api.saveOption( SIDEBAR_PREFERENCE_NAME, this.checkbox.checked ? 1 : 0 );
}

/**
 * Save sidebar state to localStorage, if available, ignore otherwise.
 *
 * @this CheckboxHack
 * @return {void}
 */
function saveSidebarLocalState() {
	var sidebarOpen = this.checkbox.checked ? '1' : '0';
	try {
		localStorage.setItem( 'vector-sidebar-open', sidebarOpen );
	} catch ( e ) {
	}
}

/**
 * Initialize all JavaScript sidebar enhancements.
 * Improve the interactivity of the sidebar panel by binding optional checkbox hack enhancements
 * for focus and `aria-expanded`. Also, flip the icon image on click.
 *
 * @param {any} window
 * @return {CheckboxHack}
 */
function init( window ) {
	var checkbox = window.document.getElementById( SIDEBAR_CHECKBOX_ID ),
		button = window.document.getElementById( SIDEBAR_BUTTON_ID );
	var saveSidebarState, saveSidebarServerState;
	if ( !mw.config.get( 'wgVectorDisableSidebarPersistence' ) ) {
		saveSidebarState = saveSidebarLocalState; // Logged out.
		if ( false && mw.config.get( 'wgUserName' ) ) {
			// Logged in.
			// Set the API request to fire after 1 second of inactivity
			// after an initial click. Guards agains overloading the API.
			saveSidebarServerState = debounce( 1000, saveSidebarPreference );
			saveSidebarState = function () {
				saveSidebarLocalState();
				saveSidebarServerState();
			};
		}
	}

	if ( checkbox instanceof HTMLInputElement && button ) {
		checkboxHack = new CheckboxHack( window, checkbox, button, saveSidebarState );

		// This code is delay-loaded. If the user toggled
		// the sidebar by now, its state should be saved.
		if ( saveSidebarState
			&& window.mwSidebarInitialState !== undefined
			&& window.mwSidebarInitialState !== checkbox.checked ) {
			saveSidebarState.call( checkboxHack );
		}
	}
	return checkboxHack; // Silence unused-var warnings.
}

module.exports = {
	init: init
};
