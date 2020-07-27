/**
 * @class CollapsibleTabsOptions
 * @property {string|jQuery} collapsedContainer  Selector for the container of collapsed elements.
 * @property {string} collapsible  CSS selector of collapsible elements within the containers.
 * @property {Function} expandCondition  Function returning true if one element should be expanded.
 * @property {Function} collapseCondition  Function returning true if one element should be collapsed.
 *
 * @class CollapsibleInstance
 * @property {jQuery} $eventTarget  Element receiving the event notifications.
 * @property {CollapsibleDirection} collapse  Options for one direction.
 * @property {CollapsibleDirection} expand    Options for one direction.
 */

function init() {
	/** @type {Function?} */
	var boundHandler;
	/** @type {boolean} */
	var isRTL = document.documentElement.dir === 'rtl';

	/**
	 * Collapse tabs as necessary and register resize handler for the container if not done already.
	 *
	 * @param {CollapsibleTabsOptions} options
	 */
	$.fn.collapsibleTabs = function ( options ) {
		// Merge options into the defaults
		var options = $.extend( {}, $.collapsibleTabs.defaults, options );

		// return if the function is called on an empty jquery object
		if ( !this.length ) {
			return this;
		}

		if ( !boundHandler ) {
			boundHandler = mw.util.debounce( 100, function () {
				$.collapsibleTabs.handleResize();
			} );
			$( window ).on( 'resize', boundHandler );
		}

		this.each( function () {
			var $expandedContainer = $( this );
			var $collapsedContainer;
			/** @type {CollapsibleInstance?} */
			var instance = $expandedContainer.data( 'collapsibleTabsInstance' );
			if ( instance ) {
				$.collapsibleTabs.checkInstance( instance );
				return;
			}

			$collapsedContainer = $( options.collapsedContainer );
			instance = {
				$eventTarget: $expandedContainer,
				collapse: new CollapsibleDirection( true, options.collapseCondition, $expandedContainer, $collapsedContainer, options.collapsible ),
				expand  : new CollapsibleDirection( false, options.expandCondition, $collapsedContainer, $expandedContainer, options.collapsible ),
			};
			$expandedContainer.data( 'collapsibleTabsInstance', instance );
			// Add to the managed containers.
			$.collapsibleTabs.instances.push( instance );

			// Measure expanded items' width.
			$expandedContainer.children( options.collapsible ).each( function () {
				$( this ).data( 'expandedWidth', $( this ).width() );
			} );

			// Do the necessary collapsing after page load.
			$.collapsibleTabs.checkInstance( instance );
		} );

		return this;
	};

	$.collapsibleTabs = {
		/** @type {[CollapsibleInstance]} */
		instances: [],
		defaults: {
			collapsedContainer: null,
			collapsible: 'li.collapsible',
			expandCondition: function ( eleWidth ) {
				// If there are at least eleWidth + 1 pixels of free space, expand.
				// We add 1 because .width() will truncate fractional values but .offset() will not.
				return $.collapsibleTabs.calculateTabDistance() >= eleWidth + 1;
			},
			collapseCondition: function () {
				// If there's an overlap, collapse.
				return $.collapsibleTabs.calculateTabDistance() < 0;
			}
		},

		/**
		 * Check all containers for collapse and expand when the window is resized.
		 */
		handleResize: function () {
			$.collapsibleTabs.instances.forEach( this.checkInstance );
		},

		/**
		 * Check one container for collapse and expand.
		 *
		 * @param {CollapsibleInstance} instance
		 */
		checkInstance: function ( instance ) {
			var $eventTarget = instance.$eventTarget;
			instance.collapse.checkMove( $eventTarget ) || instance.expand.checkMove( $eventTarget );
		},
		
		/**
		 * Get the amount of horizontal distance between the two tabs groups in pixels.
		 *
		 * Uses `#left-navigation` and `#right-navigation`. If negative, this
		 * means that the tabs overlap, and the value is the width of overlapping
		 * parts.
		 *
		 * Used in default `expandCondition` and `collapseCondition` options.
		 *
		 * @return {number} distance/overlap in pixels
		 */
		calculateTabDistance: function ( leftElement, rightElement ) {
			var leftEnd, rightStart;
			leftElement = leftElement || document.getElementById( 'left-navigation' );
			rightElement = rightElement || document.getElementById( 'right-navigation' );
			if ( !leftElement || !rightElement ) {
				return 0;
			}

			// In RTL, #right-navigation is actually on the left and vice versa.
			// Hooray for descriptive naming.
			if ( isRTL ) {
				leftEnd = leftElement;
				leftElement = rightElement;
				rightElement = leftEnd;
			}

			leftEnd = leftElement.getBoundingClientRect().right;
			rightStart = rightElement.getBoundingClientRect().left;
			return rightStart - leftEnd;
		}
	};

	/**
	 * @constructor
	 * @param {boolean} collapse  true if this is the collapse direction, false if the expand.
	 * @param {Function} moveCondition  Function returning true if one element should be moved.
	 * @param {jQuery} $sourceContainer  Element to move items from.
	 * @param {jQuery} $targetContainer  Element to move items into.
	 * @param {string} itemSelector  CSS selector of collapsible items within the containers.
	 */
	function CollapsibleDirection( collapse, moveCondition, $sourceContainer, $targetContainer, itemSelector ) {
		this.collapse = collapse;
		this.moveItem = collapse ? this.moveToCollapsed : this.moveToExpanded;
		this.moveCondition = moveCondition;
		this.$sourceContainer = $sourceContainer;
		this.$targetContainer = $targetContainer;
		this.itemSelector = itemSelector;
		//this.beforeEvent = collapse ? 'beforeTabCollapse' : 'beforeTabExpand';
		this.afterEvent  = collapse ?  'afterTabCollapse' :  'afterTabExpand';
	}

	/**
	 * @param {jQuery} $eventTarget  The element to send the notification events to.
	 * @return {boolean}  true if a move happened.
	 */
	CollapsibleDirection.prototype.checkMove = function ( $eventTarget ) {
		var $tab;
		var changed = false;
		var last;
		// if the two navigations are colliding
		while ( ( $tab = this.getItemToMove() ) && $tab.length && $tab[0] != last ) {
			last = $tab[0];
			if ( !changed ) {
				changed = true;
				// Historically the events were fired before the move.
				//$eventTarget.trigger( this.beforeEvent );
			}
			// Move the element to the other container.
			this.moveItem( $tab );
		}
		if ( changed ) {
			$eventTarget.trigger( this.afterEvent );
		}
		return changed;
	};

	/**
	 * @return {jQuery?}  Element to move.
	 */
	CollapsibleDirection.prototype.getItemToMove = function () {
		var $items = this.$sourceContainer.children( this.itemSelector );
		var $item = this.collapse ? $items.last() : $items.first();
		// Items initially added to collapsedContainer have no width measurement.
		// Avoid a first-time flicker of an "expand -> too wide -> collapse" cycle by
		// using the item's width in the collapsed container which might be bigger
		// than in the expanded container.
		var elementWidth = $item.data( 'expandedWidth' ) || $item.width() || ( this.collapse ? 0 : 100 );
		if ( $item.length && this.moveCondition.call( null, elementWidth, $items ) ) {
			return $item;
		}
	};

	/**
	 * @param {jQuery} $item  Element to move.
	 */
	CollapsibleDirection.prototype.moveToCollapsed = function ( $item ) {
		// Add a placeholder.
		$( '<span>' ).addClass( 'placeholder' ).css( 'display', 'none' ).insertAfter( $item );
		// Remove the element from where it's at and put it in the dropdown menu.
		this.$targetContainer.prepend( $item );
	};

	/**
	 * @param {jQuery} $item  Element to move.
	 */
	CollapsibleDirection.prototype.moveToExpanded = function ( $item ) {
		var $placeholder = this.$targetContainer.children( '.placeholder' ).first();
		var elementWidth;
		if ( $placeholder.length ) {
			$placeholder.replaceWith( $item );
		} else {
			this.$targetContainer.append( $item );
		}
		// Update the 'expandedWidth' in case someone was brazen enough to
		// change the tab's contents after the page load *gasp* (T71729). This
		// doesn't prevent a tab from collapsing back and forth once, but at
		// least it won't continue to do that forever.
		elementWidth = $item.width();
		if ( elementWidth ) {
			$item.data( 'expandedWidth', elementWidth );
		}
	};
}

module.exports = Object.freeze( { init: init } );
