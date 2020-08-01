/**
 * Collapsible tabs for Vector
 */
function initCollapsing() {
	/* eslint-disable no-jquery/no-global-selector */
	var leftElement = document.getElementById( 'left-navigation' );
	var rightElement = document.getElementById( 'right-navigation' );
	var overflowElement = document.getElementById( 'mw-head' );
	var $expandedContainer = $( '#p-views ul' ); // Renamed from $tabContainer.
	var $collapsedContainer = $( '#p-cactions ul' ); // Renamed from $cactions.
	//var $expandedContainer = $( '.menu-actions > .vector-menu-content-list' ); // Renamed from $tabContainer.
	//var $collapsedContainer = $( '.menu-more > .vector-menu-content-list' ); // Renamed from $cactions.
	var collapsibleSelector = '.collapsible';
	var collapsedMenuWidth = $collapsedContainer.width() || 0;

	var options = {
		collapsedContainer: $collapsedContainer,
		collapsible: collapsibleSelector,

		expandCondition: function ( eleWidth, $collapsedItems ) {
			var distance = $.collapsibleTabs.calculateTabDistance( leftElement, rightElement );
			var marginLeft = parseFloat( window.getComputedStyle( rightElement ).marginLeft );
			// If there are at least eleWidth + 1 pixels of free space, expand.
			// We add 1 because .width() will truncate fractional values but .offset() will not.

			if ( distance >= eleWidth + 1 ) {
				return true;
			} else {
				// Maybe we can still expand? Account for the width of the "Actions" dropdown
				// if the expansion would hide it.
				if ( $collapsedContainer.children().length === 1 ) {
					return distance >= eleWidth + 1 - collapsedMenuWidth;
				} else {
					return false;
				}
			}
		},

		collapseCondition: function ( eleWidth, $expandedItems ) {
			var collapsibleWidth = 0;
			var doCollapse = false;
			// Calculate overlap (negative distance) of elements if floated.
			var distance = $.collapsibleTabs.calculateTabDistance( leftElement, rightElement );
			var marginLeft = parseFloat( window.getComputedStyle( rightElement ).marginLeft );
			// Calculate width that overflows available space.
			var overflowX = overflowElement.scrollWidth - overflowElement.clientWidth;
			var overflowY = overflowElement.scrollHeight - overflowElement.clientHeight;

			// TODO: The dropdown itself should probably "fold" to just the down-arrow
			// (hiding the text) if it can't fit on the line?

			// Never collapse if there is no overflow.
			if ( distance > 0 || distance === 0 && overflowX <= 1 && overflowY <= 1 ) {
				return false;
			}

			// Collapse if the "More" button is already shown.
			// eslint-disable-next-line no-jquery/no-class-state
			if ( !$collapsedContainer.hasClass( 'emptyPortlet' ) ) {
				return true;
			}

			// If we reach here, this means:
			// 1. #p-cactions is currently empty and invisible (e.g. when logged out),
			// 2. and, there is at least one li.collapsible link in #p-views, as asserted
			//    by handleResize() before calling here. Such link exists e.g. as
			//    "View history" on articles, but generally not on special pages.
			// 3. and ˙.mw-article-toolbar˙ is overflowing, e.g. when making the window very narrow,
			//    or if a gadget added a lot of tabs.
			$expandedItems.each( function ( _index, element ) {
				collapsibleWidth += $( element ).data( 'expandedWidth' ) || 0;
				if ( collapsibleWidth > collapsedMenuWidth ) {
					// We've found one or more collapsible links that are wider
					// than the "More" menu would be if it were made visible,
					// which means it is worth doing a collapsing.
					doCollapse = true;
					// Stop this loop the moment the condition is met once.
					return false;
				}
				return;
			} );
			return doCollapse;
		}
	};

	// Bind callback functions to animate our drop down menu in and out
	// and then call the collapsibleTabs function on the menu
	$expandedContainer
		.on( 'afterTabCollapse', function () {
			// If the dropdown was hidden, show it
			// eslint-disable-next-line no-jquery/no-class-state
			$collapsedContainer.removeClass( 'emptyPortlet' );
			// Note: .classList requires IE10.
		} )
		.on( 'afterTabExpand', function () {
			// Hide the dropdown if all children are removed.
			if ( $collapsedContainer.children().length === 0 ) {
				// eslint-disable-next-line no-jquery/no-class-state
				$collapsedContainer.addClass( 'emptyPortlet' );
			}
		} )
		.collapsibleTabs( options );

	return {
		/**
		 * Check if tabs need collapsing.
		 */
		checkCollapsing: function() {
			$expandedContainer.collapsibleTabs();
		}
	};
}

function init() {
	var skinController = initCollapsing();
	var mw = window.mw;

	// Hook addPortletLink( ... ) to collapse tabs after adding a link.
	if ( mw && mw.hook && mw.util && mw.util.addPortletLink ) {
		mw.hook( 'util.addPortletLink' ).add( function ( listItem ) {
			// Adding to the expandedContainer?
			if ( $( listItem ).parent( '#p-views' ) ) {
				// Check for collapsing tabs afterwards.
				// More items can be added in the same script
				// before the check, thus retaining their order.
				setTimeout( skinController.checkCollapsing );
			}
		} );
	}

	return skinController;
}

module.exports = Object.freeze( { init: init } );
