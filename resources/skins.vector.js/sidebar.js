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

/** @interface MwApiConstructor */
/** @interface CheckboxHack */
/** @interface MwApi */

/** @type {CheckboxHack} */ var CheckboxHack =
require( /** @type {string} */( 'mediawiki.page.ready' ) ).CheckboxHack;
var SIDEBAR_BUTTON_ID = 'mw-sidebar-button',
	SIDEBAR_CHECKBOX_ID = 'mw-sidebar-checkbox',
	SIDEBAR_PREFERENCE_NAME = 'VectorSidebarVisible';

var debounce = require( /** @type {string} */ ( 'mediawiki.util' ) ).debounce;
/** @type {MwApi} */ var api;

/**
 * Execute a debounced API request to save the sidebar user preference.
 * The request is meant to fire 1000 milliseconds after the last click on
 * the sidebar button.
 *
 * @param {HTMLInputElement} checkbox
 * @return {any}
 */
function saveSidebarState( checkbox ) {
	return debounce( 1000, function () {
		api = api || new mw.Api();
		api.saveOption( SIDEBAR_PREFERENCE_NAME, checkbox.checked ? 1 : 0 );
	} );
}

/**
 * Bind the event handler that saves the sidebar state to the click event
 * on the sidebar button.
 *
 * @param {HTMLElement|null} checkbox
 * @param {HTMLElement|null} button
 */
function bindSidebarClickEvent( checkbox, button ) {
	if ( checkbox instanceof HTMLInputElement && button ) {
		button.addEventListener( 'click', saveSidebarState( checkbox ) );
	}
}

/**
 * Initialize all JavaScript sidebar enhancements.
 *
 * @param {Window} window
 * @return {void}
 */
function init( window ) {
	var checkbox = window.document.getElementById( SIDEBAR_CHECKBOX_ID ),
		button = window.document.getElementById( SIDEBAR_BUTTON_ID );

	if ( checkbox instanceof HTMLInputElement && button ) {
		// eslint-disable-next-line no-unused-vars
		checkboxHack = new CheckboxHack( window, checkbox, button, {} );
	}

	if ( mw.config.get( 'wgUserName' ) ) {
		bindSidebarClickEvent( checkbox, button );
	}
}

module.exports = {
	init: init
};
