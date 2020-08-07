/** @type {CheckboxHack} */
var CheckboxHack = require( /** @type {string} */ ( 'mediawiki.page.ready' ) ).CheckboxHack;
var collapsibleTabs = require( '../skins.vector.legacy.js/collapsibleTabs.js' ),
	vector = require( '../skins.vector.legacy.js/vector.js' ),
	sidebar = require( './sidebar.js' );

/**
 * Wait for first paint before calling this function. That's its whole purpose.
 *
 * Some CSS animations and transitions are "disabled" by default as a workaround to this old Chrome
 * bug, https://bugs.chromium.org/p/chromium/issues/detail?id=332189, which otherwise causes them to
 * render in their terminal state on page load. By adding the `vector-animations-ready` class to the
 * `html` root element **after** first paint, the animation selectors suddenly match causing the
 * animations to become "enabled" when they will work properly. A similar pattern is used in Minerva
 * (see T234570#5779890, T246419).
 *
 * Example usage in Less:
 *
 * ```less
 * .foo {
 *     color: #f00;
 *     .transform( translateX( -100% ) );
 * }
 *
 * // This transition will be disabled initially for JavaScript users. It will never be enabled for
 * // no-JS users.
 * .vector-animations-ready .foo {
 *     .transition( transform 100ms ease-out; );
 * }
 * ```
 *
 * @param {Document} document
 * @return {void}
 */
function enableCssAnimations( document ) {
	document.documentElement.classList.add( 'vector-animations-ready' );
}

/**
 * Initialization when document is ready.
 */
function init() {
	var skinController = vector.init();
	var checkbox = document.getElementById( sidebar.SIDEBAR_CHECKBOX_ID );

	// Collapse tabs as necessary when sidebar is toggled.
	$( checkbox ).on( 'input', function() {
		skinController.checkCollapsing();
	} );

	initDropdowns( window );
}

/**
 * Initialize all JS dropdown enhancements.
 * Improve the interactivity of the dropdown menus by binding optional checkbox hack enhancements
 * for focus and `aria-expanded`.
 *
 * @param {any} window
 * @return {CheckboxHack}
 */
function initDropdowns( window ) {
	var menus, menu, checkbox, button, popup, checkboxHack;
	menus = window.document.getElementsByClassName( 'vector-menu-dropdown' );
	for (i = 0; i < menus.length; i++) {
		menu = menus[i];
		checkbox = menu.getElementsByClassName( 'vector-menu-checkbox' )[0];
		// button   = menu.getElementsByClassName( 'vector-menu--button' )[0];
		button   = checkbox;
		popup    = menu.getElementsByClassName( 'vector-menu--list' )[0];
		if ( checkbox instanceof HTMLInputElement && button ) {
			checkboxHack = new CheckboxHack( window, checkbox, button, { autoHideElement: popup } );
		}
	}
	return checkboxHack; // Silence warning.
}

/**
 * @param {Window} window
 * @return {void}
 */
function main( window ) {
	enableCssAnimations( window.document );
	sidebar.init( window );
	collapsibleTabs.init();
	$( init );
}

main( window );
