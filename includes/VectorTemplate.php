<?php
/**
 * Vector - Modern version of MonoBook with fresh look and many usability
 * improvements.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Skins
 */

use Vector\Constants;

/**
 * QuickTemplate subclass for Vector
 * @ingroup Skins
 * @deprecated Since 1.35, duplicate class locally if its functionality is needed.
 * Extensions or skins should extend it under no circumstances.
 */
class VectorTemplate extends BaseTemplate {
	/** @var int */
	private const MENU_TYPE_DEFAULT = 0;
	/** @var int */
	private const MENU_TYPE_TABS = 1;
	/** @var int */
	private const MENU_TYPE_DROPDOWN = 2;
	/** @var int */
	private const MENU_TYPE_PORTAL = 3;

	/** @var array of alternate message keys for menu labels */
	private const MENU_LABEL_KEYS = [
		'cactions' => 'vector-more-actions',
		'tb' => 'toolbox',
		'personal' => 'personaltools',
		'lang' => 'otherlanguages',
	];

	/** @var Map of menu labels => menu classes */
	private const MENU_CLASSES = [
		// User tools
		'personal' => 'menu-personal',
		// User dropdown menu
		'usermenu' => 'menu-user',
		// Left side: Page, Discuss.
		'namespaces' => 'menu-namespaces',
		// Left side: dropdown.
		'variants' => 'menu-variants',
		// Right side: View, Edit, Edit source, History, Watchstar.
		'views' => 'menu-actions',
		// Right side: "More" dropdown.
		'cactions' => 'menu-more',
		// Sidebar Toolbox
		'tb' => 'sidebar-toolbox',
		// Sidebar Languages
		'lang' => 'sidebar-languages',
		// All other sidebar portals mapped: '*' => 'sidebar-*'
	];

	/** @var Map of menu type => Vector classes */
	private const EXTRA_CLASSES = [
		self::MENU_TYPE_DROPDOWN => 'vector-menu vector-menu-dropdown vectorMenu',
		self::MENU_TYPE_TABS => 'vector-menu vector-menu-tabs vectorTabs',
		self::MENU_TYPE_PORTAL => 'vector-menu vector-menu-portal portal',
		self::MENU_TYPE_DEFAULT => 'vector-menu',
	];

	/** @var Map of menu type => label class */
	private const LABEL_CLASSES = [
		self::MENU_TYPE_DROPDOWN => 'vector-menu--button',
		self::MENU_TYPE_TABS => 'vector-menu--label',
		self::MENU_TYPE_PORTAL => 'vector-menu--heading',
		self::MENU_TYPE_DEFAULT => 'vector-menu--label',
	];

	/**
	 * T243281: Code used to track clicks to opt-out link.
	 *
	 * The "vct" substring is used to describe the newest "Vector" (non-legacy)
	 * feature. The "w" describes the web platform. The "1" describes the version
	 * of the feature.
	 *
	 * @see https://wikitech.wikimedia.org/wiki/Provenance
	 * @var string
	 */
	private const OPT_OUT_LINK_TRACKING_CODE = 'vctw1';

	/** @var TemplateParser */
	private $templateParser;
	/** @var string File name of the root (master) template without folder path and extension */
	private $templateRoot;

	/** @var bool */
	private $isLegacy;

	/**
	 * @param Config $config
	 * @param TemplateParser $templateParser
	 * @param bool $isLegacy
	 */
	public function __construct(
		Config $config,
		TemplateParser $templateParser,
		bool $isLegacy
	) {
		parent::__construct( $config );

		$this->templateParser = $templateParser;
		$this->isLegacy = $isLegacy;
		$this->templateRoot = $isLegacy ? 'skin-legacy' : 'skin';
	}

	/**
	 * @return Config
	 */
	private function getConfig() {
		return $this->config;
	}

	/**
	 * The template parser might be undefined. This function will check if it set first
	 *
	 * @return TemplateParser
	 */
	protected function getTemplateParser() {
		if ( $this->templateParser === null ) {
			throw new \LogicException(
				'TemplateParser has to be set first via setTemplateParser method'
			);
		}
		return $this->templateParser;
	}

	/**
	 * Add calculated 'height--em' value to logo config.
	 *
	 * @param array $logoImage Logo config data.
	 * @param integer $cssFontSizePx Font-size defined in the CSS in pixels.
	 */
	private static function addLogoHeightEm( &$logoImage, $cssFontSizePx ) : void {
		$height = $logoImage['height'] ?? null;
		if ( $height ) {
			$logoImage['height--em'] = ( $height / $cssFontSizePx ) . 'em';
		}
	}

	/**
	 * @deprecated Please use Skin::getTemplateData instead
	 * @return array Returns an array of data shared between Vector and legacy
	 * Vector.
	 */
	private function getSkinData() : array {
		// @phan-suppress-next-line PhanUndeclaredMethod
		$contentNavigation = $this->getSkin()->getMenuProps();
		$skin = $this->getSkin();
		$out = $skin->getOutput();
		$title = $out->getTitle();

		$rawSidebarInitScript = null;
		$disablePersistance = $this->getConfig()->get( Constants::CONFIG_KEY_DISABLE_SIDEBAR_PERSISTENCE );
		$sidebarStateClientSide = true || !$skin->getUser()->isLoggedIn();

		if ( !$disablePersistance && $sidebarStateClientSide ) {
			$rawSidebarInitScript = file_get_contents( __DIR__ . '/../resources/skins.vector.js/sidebar-init.js' );
			$debug = $out->getRequest()->getRawVal( 'debug' ) === 'true';
			if ( !$debug ) {
				$rawSidebarInitScript = ResourceLoader::filter(
					'minify-js', $rawSidebarInitScript, [ 'cache' => true ]
				);
			}
			$rawSidebarInitScript = "<script>\n" . $rawSidebarInitScript . "\n</script>\n";
		}

		// Naming conventions for Mustache parameters.
		//
		// Value type (first segment):
		// - Prefix "is" or "has" for boolean values.
		// - Prefix "msg-" for interface message text.
		// - Prefix "html-" for raw HTML.
		// - Prefix "raw-" for raw inlined content (JS, CSS).
		// - Prefix "data-" for an array of template parameters that should be passed directly
		//   to a template partial.
		// - Prefix "array-" for lists of any values.
		//
		// Source of value (first or second segment)
		// - Segment "page-" for data relating to the current page (e.g. Title, WikiPage, or OutputPage).
		// - Segment "hook-" for any thing generated from a hook.
		//   It should be followed by the name of the hook in hyphenated lowercase.
		//
		// Conditionally used values must use null to indicate absence (not false or '').
		$mainPageHref = Skin::makeMainPageUrl();
		// From Skin::getNewtalks(). Always returns string, cast to null if empty.
		$newTalksHtml = $skin->getNewtalks() ?: null;

		// @phan-suppress-next-line PhanUndeclaredMethod
		$commonSkinData = $skin->getTemplateData() + [
			'html-headelement' => $out->headElement( $skin ),
			'page-langcode' => $title->getPageViewLanguage()->getHtmlCode(),
			'page-isarticle' => (bool)$out->isArticle(),

			// Remember that the string '0' is a valid title.
			// From OutputPage::getPageTitle, via ::setPageTitle().
			'html-title' => $out->getPageTitle(),
			'msg-tagline' => $skin->msg( 'tagline' )->text(),

			'html-newtalk' => $newTalksHtml ? '<div class="usermessage">' . $newTalksHtml . '</div>' : '',

			'msg-vector-jumptonavigation' => $skin->msg( 'vector-jumptonavigation' )->text(),
			'msg-vector-jumptosearch' => $skin->msg( 'vector-jumptosearch' )->text(),

			'html-printfooter' => $skin->printSource(),
			'html-categories' => $skin->getCategories(),
			'data-footer' => $this->getFooterData(),
			'html-navigation-heading' => $skin->msg( 'navigation-heading' ),
			'data-search-box' => $this->buildSearchProps(),
			'is-layout-new-search' => $this->getConfig()->get( Constants::CONFIG_KEY_LAYOUT_NEW_SEARCH ),

			// Header
			'data-logos' => ResourceLoaderSkinModule::getAvailableLogos( $this->getConfig() ),
			'msg-sitetitle' => $skin->msg( 'sitetitle' )->text(),
			'msg-sitesubtitle' => $skin->msg( 'sitesubtitle' )->text(),
			'main-page-href' => $mainPageHref,

			'raw-sidebar-init-script' => $rawSidebarInitScript,
			'data-sidebar' => $this->buildSidebar(),
			'sidebar-visible' => $this->isSidebarVisible(),
			'msg-vector-action-toggle-sidebar' => $skin->msg( 'vector-action-toggle-sidebar' )->text(),
		] + $this->getMenuProps();
		
		$logos = &$commonSkinData['data-logos'];
		// font-size declared in Logo.less
		self::addLogoHeightEm( $logos['wordmark'], 22.4 ); // font-size: 1.4em; -> 22.4px
		self::addLogoHeightEm( $logos['tagline'], 11.2 ); // font-size: 0.7em; -> 11.2px

		// The following logic is unqiue to Vector (not used by legacy Vector) and
		// is planned to be moved in a follow-up patch.
		if ( !$this->isLegacy && $skin->getUser()->isLoggedIn() ) {
			$commonSkinData['data-sidebar']['data-emphasized-sidebar-action'] = [
				'href' => SpecialPage::getTitleFor(
					'Preferences',
					false,
					'mw-prefsection-rendering-skin-skin-prefs'
				)->getLinkURL( 'wprov=' . self::OPT_OUT_LINK_TRACKING_CODE ),
				'text' => $skin->msg( 'vector-opt-out' )->text(),
				'title' => $skin->msg( 'vector-opt-out-tooltip' )->text(),
			];
		}

		return $commonSkinData;
	}

	/**
	 * Renders the entire contents of the HTML page.
	 */
	public function execute() {
		$tp = $this->getTemplateParser();
		echo $tp->processTemplate( $this->templateRoot, $this->getSkinData() );
	}

	/**
	 * Get rows that make up the footer
	 * @return array for use in Mustache template describing the footer elements.
	 */
	private function getFooterData() : array {
		$skin = $this->getSkin();
		$footerRows = [];
		foreach ( $this->getFooterLinks() as $category => $links ) {
			$items = [];
			$rowId = "footer-$category";

			foreach ( $links as $link ) {
				$items[] = [
					'id' => "$rowId-$link",
					'html' => $this->get( $link, '' ),
				];
			}

			$footerRows[] = [
				'id' => $rowId,
				'className' => null,
				'array-items' => $items
			];
		}

		// If footer icons are enabled append to the end of the rows
		$footerIcons = $this->get( 'footericons' );
		// 'icononly'
		foreach ( $footerIcons as $footerIconsKey => &$footerIconsBlock ) {
			foreach ( $footerIconsBlock as $footerIconKey => $footerIcon ) {
				if ( !is_string( $footerIcon ) && !isset( $footerIcon['src'] ) ) {
					unset( $footerIconsBlock[$footerIconKey] );
				}
			}
			if ( $footerIconsBlock === [] ) {
				unset( $footerIcons[$footerIconsKey] );
			}
		}
		if ( count( $footerIcons ) > 0 ) {
			$items = [];
			foreach ( $footerIcons as $blockName => $blockIcons ) {
				$html = '';
				foreach ( $blockIcons as $icon ) {
					$html .= $skin->makeFooterIcon( $icon );
				}
				$items[] = [
					'id' => 'footer-' . htmlspecialchars( $blockName ) . 'ico',
					'html' => $html,
				];
			}

			$footerRows[] = [
				'id' => 'footer-icons',
				'className' => 'noprint',
				'array-items' => $items,
			];
		}

		ob_start();
		Hooks::run( 'VectorBeforeFooter', [], '1.35' );
		$htmlHookVectorBeforeFooter = ob_get_contents();
		ob_end_clean();

		$data = [
			'html-hook-vector-before-footer' => $htmlHookVectorBeforeFooter,
			'array-footer-rows' => $footerRows,
		];

		return $data;
	}

	/**
	 * Determines wheather the initial state of sidebar is visible on not
	 *
	 * @return bool
	 */
	private function isSidebarVisible() {
		$skin = $this->getSkin();
		if ( $skin->getUser()->isLoggedIn() ) {
			$userPrefSidebarState = $skin->getUser()->getOption(
				Constants::PREF_KEY_SIDEBAR_VISIBLE
			);

			$defaultLoggedinSidebarState = $this->getConfig()->get(
				Constants::CONFIG_KEY_DEFAULT_SIDEBAR_VISIBLE_FOR_AUTHORISED_USER
			);

			// If the sidebar user preference has been set, return that value,
			// if not, then the default sidebar state for logged-in users.
			return ( $userPrefSidebarState !== null )
				? (bool)$userPrefSidebarState
				: $defaultLoggedinSidebarState;
		}
		return $this->getConfig()->get(
			Constants::CONFIG_KEY_DEFAULT_SIDEBAR_VISIBLE_FOR_ANONYMOUS_USER
		);
	}

	/**
	 * Render a series of portals
	 *
	 * @return array
	 */
	private function buildSidebar() : array {
		$skin = $this->getSkin();
		$portals = $skin->buildSidebar();
		$props = [];
		$languages = null;

		// Render portals
		foreach ( $portals as $name => $content ) {
			if ( $content === false ) {
				continue;
			}

			// Numeric strings gets an integer when set as key, cast back - T73639
			$name = (string)$name;

			switch ( $name ) {
				case 'SEARCH':
					break;
				case 'TOOLBOX':
					$props[] = $this->getMenuData(
						'tb', $content,  self::MENU_TYPE_PORTAL
					);
					break;
				case 'LANGUAGES':
					$portal = $this->getMenuData(
						'lang', $content, self::MENU_TYPE_PORTAL
					);
					// The language portal will be added provided either
					// languages exist or there is a value in html-after-portal
					// for example to show the add language wikidata link (T252800)
					if ( count( $content ) || $portal['html-after-portal'] ) {
						$languages = $portal;
					}
					break;
				default:
					// Historically some portals have been defined using HTML rather than arrays.
					// Let's move away from that to a uniform definition.
					if ( !is_array( $content ) ) {
						$html = $content;
						$content = [];
						wfDeprecated(
							"`content` field in portal $name must be array."
								. "Previously it could be a string but this is no longer supported.",
							'1.35.0'
						);
					} else {
						$html = false;
					}
					$portal = $this->getMenuData(
						$name, $content, self::MENU_TYPE_PORTAL
					);
					if ( $html ) {
						$portal['html-items'][] = $html;
					}
					$props[] = $portal;
					break;
			}
		}

		$firstPortal = $props[0] ?? null;
		if ( $firstPortal ) {
			$firstPortal[ 'class' ] .= ' portal-first';
		}

		return [
			'has-logo' => $this->isLegacy,
			'html-logo-attributes' => Xml::expandAttributes(
				Linker::tooltipAndAccesskeyAttribs( 'p-logo' ) + [
					'class' => 'mw-wiki-logo',
					'href' => Skin::makeMainPageUrl(),
				]
			),
			'array-portals-rest' => array_slice( $props, 1 ),
			'data-portals-first' => $firstPortal,
			'data-portals-languages' => $languages,
		];
	}

	const TEXTWRAPPER_PLAIN = [ 'text-wrapper' => [ 'tag' => 'span' ] ];
	const TEXTWRAPPER_SCREENREADER = [ 'text-wrapper' => [ 'tag' => 'span', 'attributes' => [ 'screen-reader' => '' ] ] ];

	/**
	 * @param string $label to be used to derive the id and human readable label of the menu
	 *  If the key has an entry in the constant MENU_LABEL_KEYS then that message will be used for the
	 *  human readable text instead.
	 * @param array $urls to convert to list items stored in 'html-items' key as an array of HTML strings
	 * @param int $type of menu (optional) - a plain list (MENU_TYPE_DEFAULT),
	 *   a tab (MENU_TYPE_TABS) or a dropdown (MENU_TYPE_DROPDOWN)
	 * @param array $options (optional) to be passed to makeListItem
	 * @param bool $setLabelToSelected (optional) the menu label will take the value of the
	 *  selected item if found.
	 * @return array
	 */
	private function getMenuData(
		string $label,
		array $urls = [],
		int $type = self::MENU_TYPE_DEFAULT,
		array $options = [],
		bool $setLabelToSelected = false
	) : array {
		$skin = $this->getSkin();
		$isPortal = self::MENU_TYPE_PORTAL === $type;

		// For some menu items, there is no language key corresponding with its menu key.
		// These inconsitencies are captured in MENU_LABEL_KEYS
		$msgObj = $skin->msg( self::MENU_LABEL_KEYS[ $label ] ?? $label );
		$menuId = 'p-' . $label;
		$props = [
			'tag' => 'nav',
			'id' => $menuId,
			'label-class' => self::LABEL_CLASSES[ $type ] ?? null,
			// If no message exists fallback to plain text (T252727)
			'label' => $msgObj->exists() ? $msgObj->text() : $label,
			'html-items' => [],
			'is-dropdown' => self::MENU_TYPE_DROPDOWN === $type,
			'html-tooltip' => Linker::tooltip( $menuId ),
			'is-portal' => $isPortal,
		];
		$htmlItems = &$props['html-items'];

		foreach ( $urls as $key => $item ) {
			// Add CSS class 'collapsible' to all links EXCEPT watchstar.
			$itemOptions = $item['options'] ?? [];
			if (
				$key !== 'watch' && $key !== 'unwatch' &&
				isset( $options['vector-collapsible'] ) && $options['vector-collapsible'] ) {
				if ( !isset( $item['class'] ) ) {
					$item['class'] = '';
				}
				$item['class'] = rtrim( 'collapsible ' . $item['class'], ' ' );
			}
			$htmlItems[] = $skin->makeListItem( $key, $item, $itemOptions + $options );

			// Check the class of the item for a `selected` class and if so, propagate the items
			// label to the main label.
			if ( $setLabelToSelected ) {
				if ( isset( $item['class'] ) && stripos( $item['class'], 'selected' ) !== false ) {
					$props['label'] = $item['text'];
				}
			}
		}

		$props['html-after-portal'] = $isPortal ? $this->getAfterPortlet( $label ) : '';

		$menuClass = self::MENU_CLASSES[$label] ?? null;
		if ( $isPortal && !$menuClass ) {
			$menuClass = 'sidebar-' . $label;
		}

		if ( $menuClass ) {
			$menuClass .= ' ';
		}

		$class = ( $menuClass ?? '' ) . self::EXTRA_CLASSES[$type];

		// Mark the portal as empty if it has no content
		if ( count( $urls ) == 0 && !$props['html-after-portal'] ) {
			$class .= ' emptyPortlet';
		}

		$props['class'] = $class;
		return $props;
	}

	public const ICON_CLASS_PREFIX = 'mw-ui-icon mw-ui-icon-';

	private function preprocessPersonalTools( array &$personalTools, Skin $skin ) {
		// Icons chosen from:  https://doc.wikimedia.org/oojs-ui/master/demos/?page=icons&theme=wikimediaui&direction=ltr&platform=desktop
		if ( $personalTools['watchlist'] ?? null ) {
			// Used:  content.history, alternative:  layout.recentChanges
			$personalTools['watchlist']['links'][0]['class'][] = self::ICON_CLASS_PREFIX . 'history';
			$personalTools['watchlist']['links'][0]['text'] = ''; // Icon only.
		}

		if ( $personalTools['anonuserpage'] ?? null ) {
			//$personalTools['anonuserpage']['links'][0]['class'] = self::ICON_CLASS_PREFIX . 'userAnonymous'; // From 'oojs-ui.styles.icons-user'
			$personalTools['anonuserpage']['links'][0]['class'] = self::ICON_CLASS_PREFIX . 'userAvatarOutline'; // From 'oojs-ui.styles.icons-user'
		}
		if ( $personalTools['userpage'] ?? null ) {
			$personalTools['userpage']['links'][0]['text'] = $skin->msg( 'nstab-user' )->text();
			$personalTools['userpage']['links'][0]['class'] = self::ICON_CLASS_PREFIX . 'userAvatar'; // From 'oojs-ui.styles.icons-user'
			//$personalTools['userpage']['links'][0]['class'] = self::ICON_CLASS_PREFIX . 'userAvatarOutline'; // From 'oojs-ui.styles.icons-user'
		}
		if ( $personalTools['mytalk'] ?? null ) {
			$personalTools['mytalk']['links'][0]['text'] .= ' ' . strtolower( $skin->msg( 'mypage' )->text() );
			$personalTools['mytalk']['links'][0]['class'] = self::ICON_CLASS_PREFIX . 'userTalk'; // From 'oojs-ui.styles.icons-user'
		}
		if ( $personalTools['preferences'] ?? null ) {
			$personalTools['preferences']['links'][0]['class'] = self::ICON_CLASS_PREFIX . 'settings'; // From 'oojs-ui.styles.icons-interactions'
		}
		if ( $personalTools['mycontris'] ?? null ) {
			$personalTools['mycontris']['links'][0]['class'] = self::ICON_CLASS_PREFIX . 'userContributions'; // From 'oojs-ui.styles.icons-user'
		}
		/*
		// Not using logIn icon: too obtrusive.
		if ( $personalTools['login'] ?? null ) {
			$personalTools['login']['links'][0]['class'] = self::ICON_CLASS_PREFIX . 'logIn-progressive'; // From 'oojs-ui.styles.icons-interactions'
		}
		*/
		if ( $personalTools['logout'] ?? null ) {
			$personalTools['logout']['links'][0]['class'] = self::ICON_CLASS_PREFIX . 'logOut'; // From 'oojs-ui.styles.icons-interactions'
		}

		if ( $personalTools['notifications-alert'] ?? null ) {
			// Icon 'bell' from 'oojs-ui.styles.icons-alerts'
			$classNames = &$personalTools['notifications-alert']['links'][0]['class'];
			$classNames = str_replace( 'oo-ui-icon-', self::ICON_CLASS_PREFIX, $classNames );
			$personalTools['notifications-alert']['options'] = self::TEXTWRAPPER_SCREENREADER;
		}
		if ( $personalTools['notifications-notice'] ?? null ) {
			// Icon 'tray', 'speechBubbles' from 'oojs-ui.styles.icons-alerts'
			$classNames = &$personalTools['notifications-notice']['links'][0]['class'];
			//$classNames = str_replace( 'oo-ui-icon-', self::ICON_CLASS_PREFIX, $classNames );
			$classNames = str_replace( 'oo-ui-icon-tray', self::ICON_CLASS_PREFIX . 'speechBubbles', $classNames );
			$personalTools['notifications-notice']['options'] = self::TEXTWRAPPER_SCREENREADER;
		}
	}

	/**
	 * @return array
	 */
	private function getMenuProps() : array {
		// @phan-suppress-next-line PhanUndeclaredMethod
		$contentNavigation = $this->getSkin()->getMenuProps();
		$skin = $this->getSkin();

		// If logged out users can edit then Vector shows a "Not logged in" menu item.
		// This should be upstreamed to core, with instructions for how to hide it for skins
		// that do not want it.
		$isLoggedIn = $skin->getUser()->isLoggedIn();
		$showNotLoggedIn = !$isLoggedIn && User::groupHasPermission( '*', 'edit' );

		$props = [];
		$personalTools = $this->getPersonalTools();
		$usermenuItems = null;

		if ( !$this->isLegacy ) {
			$this->preprocessPersonalTools( $personalTools, $skin );
		}

		if ( $personalTools['uls'] ?? null ) {
			$isNewSearch = $this->getConfig()->get( Constants::CONFIG_KEY_LAYOUT_NEW_SEARCH );
			if ( $isNewSearch ) {
				$props['html-lang-selector'] = $skin->makeListItem( 'uls', $personalTools['uls'], [ 'tag' => 'div' ] );
				unset( $personalTools['uls'] );
			} else {
				// This code doesn't belong here, it belongs in the UniversalLanguageSelector
				// It is here to workaround the fact that it wants to be the first item in the personal menus.
				$uls = $personalTools['uls'];
				unset( $personalTools['uls'] );
				$personalTools = [ 'uls' => $uls ] + $personalTools;
			}
		}


		if ( !$this->isLegacy ) {
			$toolKeys = [
				'uls' => true,
				'notifications-alert' => true,
				'notifications-notice' => true,
				'watchlist' => true,
			];
			$personalItems = array_intersect_key( $personalTools, $toolKeys );
			$usermenuItems = array_diff_key( $personalTools, $toolKeys );

			// No dropdown if only a single item in it.
			if ( count( $usermenuItems ) <= 1 ) {
				$personalItems += $usermenuItems;
				$usermenuItems = null;
			}
		}

		$props['data-personal-menu'] = $this->getMenuData(
			'personal',
			$personalItems ?? $personalTools,
			self::MENU_TYPE_DEFAULT,
			( $this->isLegacy ? [] : self::TEXTWRAPPER_PLAIN )
		);

		if ( $usermenuItems ) {
			// Modern layout.
			$usermenuLabel = $isLoggedIn
				? $skin->username
				: $skin->msg( 'notloggedin' )->text();
			//$props['data-personal-menu']['data-items'][]['data-submenu'] = [
			//	'data' => false, // Prevent recursion.
			$props['data-usermenu'] = [
				'tag' => 'li', // Override 'div'.
				'label-class' => 'vector-menu--button mw-ui-icon mw-ui-icon-' . ( $isLoggedIn ? 'userAvatar' : 'userAvatarOutline' ),
			] + $this->getMenuData(
				'usermenu', $usermenuItems, self::MENU_TYPE_DROPDOWN, self::TEXTWRAPPER_PLAIN
			);
			$props['data-usermenu']['msg-screen-reader'] = $props['data-usermenu']['label'];
			$props['data-usermenu']['label'] = $usermenuLabel;
		} elseif ( $showNotLoggedIn ) {
			// Legacy layout, logged-out, editing enabled.
			// For now we create a dedicated list item to avoid having to sync the API internals
			// of makeListItem.
			$notloggedin = Html::rawElement( 'li', [], Html::element( 'span',
				[ 'id' => 'pt-anonuserpage' ],
				$skin->msg( 'notloggedin' )->text()
			) );

			// Prepend "Not logged in".
			array_unshift( $props['data-personal-menu']['html-items'], $notloggedin );
		}

		return $props + [
			'data-namespace-tabs' => $this->getMenuData(
				'namespaces',
				$contentNavigation[ 'namespaces' ] ?? [],
				self::MENU_TYPE_TABS
			),
			'data-variants' => $this->getMenuData(
				'variants',
				$contentNavigation[ 'variants' ] ?? [],
				self::MENU_TYPE_DROPDOWN,
				[],
				true
			),
			'data-page-actions' => $this->getMenuData(
				'views',
				$contentNavigation[ 'views' ] ?? [],
				self::MENU_TYPE_TABS,
				[ 'vector-collapsible' => true ]
			),
			'data-page-actions-more' => $this->getMenuData(
				'cactions',
				$contentNavigation[ 'actions' ] ?? [],
				self::MENU_TYPE_DROPDOWN
			),
		];
	}

	/**
	 * @return array
	 */
	private function buildSearchProps() : array {
		$config = $this->getConfig();
		$skin = $this->getSkin();
		$props = [
			'form-action' => $config->get( 'Script' ),
			'html-button-search-fallback' => $this->makeSearchButton(
				'fulltext',
				[ 'id' => 'mw-searchButton', 'class' => 'searchButton mw-fallbackSearchButton' ]
			),
			'html-button-search' => $this->makeSearchButton(
				'go',
				[ 'id' => 'searchButton', 'class' => 'searchButton' ]
			),
			'html-input' => $this->makeSearchInput( [ 'id' => 'searchInput', 'class' => 'mw-search-input' ] ),
			'msg-search' => $skin->msg( 'search' ),
			'page-title' => SpecialPage::getTitleFor( 'Search' )->getPrefixedDBkey(),
		];
		return $props;
	}
}
