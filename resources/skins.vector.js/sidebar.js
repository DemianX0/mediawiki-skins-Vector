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
function saveSidebarOpenLocalSetting() {
	var sidebarOpen = this.checkbox.checked ? '1' : '0';
	try {
		localStorage.setItem( 'mw-sidebar-open', sidebarOpen );
	} catch ( e ) {
	}
}

/**
 * Save selected sidebar panel to localStorage, if available, ignore otherwise.
 *
 * @this CheckboxHack
 * @return {void}
 */
function saveSidebarPanelLocalSetting() {
	var sidebarPanel = this.checkbox.checked && this.checkbox.value;
	try {
		if ( sidebarPanel ) {
			localStorage.setItem( 'mw-sidebar-panel', sidebarPanel );
		}
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
	var sidebar, buttons,
		checkbox = window.document.getElementById( SIDEBAR_CHECKBOX_ID ),
		button = window.document.getElementById( SIDEBAR_BUTTON_ID );
	var saveSidebarOpen, saveSidebarOpenOnServer;
	if ( !mw.config.get( 'wgVectorDisableSidebarPersistence' ) ) {
		saveSidebarOpen = saveSidebarOpenLocalSetting; // Logged out.
		if ( false && mw.config.get( 'wgUserName' ) ) {
			// Logged in.
			// Set the API request to fire after 1 second of inactivity
			// after an initial click. Guards agains overloading the API.
			saveSidebarOpenOnServer = debounce( 1000, saveSidebarPreference );
			saveSidebarOpen = function () {
				saveSidebarOpenLocalSetting();
				saveSidebarOpenOnServer();
			};
		}
	}

	if ( checkbox instanceof HTMLInputElement && button ) {
		checkboxHack = new CheckboxHack( window, checkbox, button, saveSidebarOpen );

		// This code is delay-loaded. If the user toggled
		// the sidebar by now, its state should be saved.
		if ( saveSidebarOpen
			&& window.mwSidebarOpenState !== undefined
			&& window.mwSidebarOpenState !== checkbox.checked ) {
			saveSidebarOpen.call( checkboxHack );
			window.mwSidebarOpenState = checkbox.checked;
		}
	}

	sidebar = window.document.getElementsByClassName( 'mw-sidebar-container' )[0];
	buttons = sidebar.getElementsByClassName( 'mw-sidebar-button' );
	for (i = 0; i < buttons.length; i++) {
		button = buttons[i];
		checkbox = window.document.getElementById( button.getAttribute( 'for' ) );
		if ( checkbox instanceof HTMLInputElement ) {
			checkboxHack = new CheckboxHack( window, checkbox, button, saveSidebarPanelLocalSetting );
			if ( checkbox.checked
				&& window.mwSidebarPanelState !== undefined
				&& window.mwSidebarPanelState !== checkbox.value ) {
				saveSidebarPanelLocalSetting.call( checkboxHack );
				window.mwSidebarPanelState = checkbox.value;
			}
		}
	}

	return checkboxHack; // Silence unused-var warnings.
}

module.exports = {
	init: init,
	SIDEBAR_CHECKBOX_ID: SIDEBAR_CHECKBOX_ID,
	SIDEBAR_BUTTON_ID: SIDEBAR_BUTTON_ID,
};
